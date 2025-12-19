<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class CartService
{
    const MAX_QUANTITY_PER_ITEM = 5000;

    public function getCart(string $userId)
    {
        // GetOrCreate Logic
        $cart = Cart::firstOrCreate(
            ['user_id' => $userId],
            ['last_updated' => now()]
        );

        // Carrega items com produtos
        $cart->load('items.product');

        // Limpeza de órfãos (produtos deletados ou inativos)
        $orphans = $cart->items->filter(fn($item) => !$item->product || !$item->product->is_active);
        
        if ($orphans->isNotEmpty()) {
            CartItem::destroy($orphans->pluck('id'));
            $cart->refresh(); // Recarrega após deletar
        }

        // Formatação do DTO de resposta
        $itemsDto = $cart->items->map(function ($item) {
            $product = $item->product;
            // Assumindo que image_urls é array no cast do model Product
            $mainImage = !empty($product->image_urls) ? $product->image_urls[0] : "";

            return [
                'id' => $item->id,
                'productId' => $item->product_id,
                'productName' => $product->name,
                'imageUrl' => $mainImage,
                'unitPrice' => $product->price,
                'quantity' => $item->quantity,
                'totalPrice' => $product->price * $item->quantity,
                'weight' => $product->weight,
                'dimensions' => [
                    'width' => $product->width,
                    'height' => $product->height,
                    'length' => $product->length
                ]
            ];
        });

        return [
            'id' => $cart->id,
            'items' => $itemsDto,
            'totalAmount' => $itemsDto->sum('totalPrice')
        ];
    }

    public function addItem(string $userId, array $dto)
    {
        if ($dto['quantity'] <= 0) throw new Exception("Quantidade inválida.");
        if ($dto['quantity'] > self::MAX_QUANTITY_PER_ITEM) throw new Exception("Limite de quantidade excedido.");

        // Laravel gerencia transações e retries de deadlock automaticamente (3 tentativas padrão)
        DB::transaction(function () use ($userId, $dto) {
            // LockForUpdate para evitar condição de corrida no estoque
            $product = Product::where('id', $dto['productId'])->lockForUpdate()->first();

            if (!$product) throw new Exception("Produto indisponível.");
            if ($product->stock_quantity < $dto['quantity']) throw new Exception("Estoque insuficiente.");

            $cart = Cart::firstOrCreate(['user_id' => $userId]);

            $existingItem = CartItem::where('cart_id', $cart->id)
                ->where('product_id', $dto['productId'])
                ->first();

            if ($existingItem) {
                $newTotal = $existingItem->quantity + $dto['quantity'];
                
                if ($newTotal > self::MAX_QUANTITY_PER_ITEM) throw new Exception("Limite excedido.");
                if ($product->stock_quantity < $newTotal) throw new Exception("Estoque insuficiente para o total.");

                $existingItem->quantity = $newTotal;
                $existingItem->save();
            } else {
                CartItem::create([
                    'cart_id' => $cart->id,
                    'product_id' => $dto['productId'],
                    'quantity' => $dto['quantity']
                ]);
            }

            $cart->update(['last_updated' => now()]);
        }, 3); // 3 tentativas
    }

    public function updateItemQuantity(string $userId, string $itemId, int quantity)
    {
        if (quantity < 0) throw new Exception("Qtd negativa.");
        if (quantity == 0) {
            $this->removeItem($userId, $itemId);
            return;
        }

        DB::transaction(function () use ($userId, $itemId, $quantity) {
            $cart = Cart::where('user_id', $userId)->firstOrFail();
            
            $item = CartItem::where('id', $itemId)
                ->where('cart_id', $cart->id)
                ->firstOrFail();

            $product = Product::where('id', $item->product_id)->lockForUpdate()->first();

            if ($product->stock_quantity < $quantity) {
                throw new Exception("Estoque insuficiente.");
            }

            $item->quantity = $quantity;
            $item->save();
            $cart->touch(); // Atualiza updated_at/last_updated
        });
    }

    public function removeItem(string $userId, string $itemId)
    {
        $cart = Cart::where('user_id', $userId)->first();
        if ($cart) {
            CartItem::where('cart_id', $cart->id)->where('id', $itemId)->delete();
            $cart->touch();
        }
    }

    public function clearCart(string $userId)
    {
        $cart = Cart::where('user_id', $userId)->first();
        if ($cart) {
            $cart->items()->delete();
            $cart->touch();
        }
    }
}