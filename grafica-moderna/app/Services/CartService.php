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

    public function getCart(string $userId): Cart
    {
        // GetOrCreate logic igual ao C#
        $cart = Cart::firstOrCreate(
            ['user_id' => $userId],
            ['last_updated' => now()]
        );

        // Carrega items e produtos para montar o DTO
        $cart->load('items.product');

        // Lógica de Órfãos (Produtos nulos ou inativos) - C# Linhas 29-34
        $orphanedItems = $cart->items->filter(function ($item) {
            return $item->product === null || !$item->product->is_active;
        });

        if ($orphanedItems->isNotEmpty()) {
            CartItem::destroy($orphanedItems->pluck('id'));
            $cart->refresh(); // Recarrega o carrinho limpo
            // Commit é automático no final da request do Laravel, diferente do C# explícito
        }

        return $cart;
    }

    public function addItem(string $userId, array $dto): void
    {
        if (empty($userId)) throw new Exception("userId inválido.");
        if ($dto['quantity'] <= 0) throw new Exception("Quantidade deve ser maior que zero.");
        if ($dto['quantity'] > self::MAX_QUANTITY_PER_ITEM) 
            throw new Exception("Quantidade excede o limite permitido de " . self::MAX_QUANTITY_PER_ITEM . ".");

        // Loop de retry do C# é abstraído pelo segundo parâmetro do transaction (3 tentativas)
        DB::transaction(function () use ($userId, $dto) {
            
            // GetByIdWithLockAsync (C#) -> lockForUpdate (Laravel)
            $product = Product::where('id', $dto['productId'])->lockForUpdate()->first();

            if (!$product || !$product->is_active) 
                throw new Exception("Produto indisponível ou removido.");

            if ($product->stock_quantity < $dto['quantity'])
                throw new Exception("Estoque insuficiente para a quantidade solicitada.");

            $cart = Cart::firstOrCreate(
                ['user_id' => $userId],
                ['last_updated' => now()]
            );

            // Bloqueia o item do carrinho se existir
            $existingItem = CartItem::where('cart_id', $cart->id)
                ->where('product_id', $dto['productId'])
                ->lockForUpdate() 
                ->first();

            if ($existingItem) {
                $newTotal = $existingItem->quantity + $dto['quantity'];

                if ($newTotal > self::MAX_QUANTITY_PER_ITEM)
                    throw new Exception("O total de itens excederia o limite máximo de " . self::MAX_QUANTITY_PER_ITEM . ".");

                // Em PHP int overflow vira float, mas verificamos lógica
                if ($product->stock_quantity < $newTotal)
                    throw new Exception("Não é possível adicionar mais itens: estoque insuficiente.");

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

            Log::info("Item adicionado ao carrinho. User: {$userId}, Product: {$dto['productId']}, Qty: {$dto['quantity']}");

        }, 3); // 3 tentativas (MaxConcurrencyRetries)
    }

    public function updateItemQuantity(string $userId, string $itemId, int $quantity): void
    {
        if (empty($userId)) throw new Exception("userId inválido.");
        if ($quantity < 0) throw new Exception("A quantidade não pode ser negativa.");
        if ($quantity > self::MAX_QUANTITY_PER_ITEM)
            throw new Exception("Quantidade excede o limite permitido de " . self::MAX_QUANTITY_PER_ITEM . ".");

        if ($quantity == 0) {
            $this->removeItem($userId, $itemId);
            return;
        }

        DB::transaction(function () use ($userId, $itemId, $quantity) {
            $cart = Cart::where('user_id', $userId)->first();
            if (!$cart) throw new Exception("Carrinho não encontrado.");

            $item = CartItem::where('id', $itemId)
                ->where('cart_id', $cart->id)
                ->first();
                
            if (!$item) throw new Exception("Item não encontrado no carrinho.");

            $product = Product::where('id', $item->product_id)->lockForUpdate()->first();
            
            if (!$product || !$product->is_active) throw new Exception("Produto indisponível.");

            if ($product->stock_quantity < $quantity)
                throw new Exception("Estoque insuficiente.");

            $item->quantity = $quantity;
            $item->save();
            
            $cart->update(['last_updated' => now()]);
        }, 3);
    }

    public function removeItem(string $userId, string $itemId): void
    {
        if (empty($userId)) throw new Exception("userId inválido.");

        $cart = $this->getCart($userId); // Reutiliza lógica de criar se não existir
        
        $item = $cart->items->where('id', $itemId)->first();

        if ($item) {
            $item->delete();
            $cart->touch();
        }
    }

    public function clearCart(string $userId): void
    {
        if (empty($userId)) throw new Exception("userId inválido.");

        $cart = Cart::where('user_id', $userId)->first();
        if ($cart) {
            $cart->items()->delete();
            $cart->touch();
        }
    }
}