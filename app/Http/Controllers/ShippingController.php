<?php

namespace App\Http\Controllers;

use App\Services\ContentService;
use App\Services\ProductService;
use App\Services\Interfaces\ShippingServiceInterface; 
use App\Http\Requests\CalculateShippingRequest;
use App\Http\Resources\ShippingOptionResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ShippingController extends Controller
{
    private const MAX_ITEMS_PER_CALCULATION = 50;

    protected $shippingServices; 
    protected ProductService $productService;
    protected ContentService $contentService;

    public function __construct(
        ProductService $productService,
        ContentService $contentService,
        iterable $shippingServices 
    ) {
        $this->productService = $productService;
        $this->contentService = $contentService;
        $this->shippingServices = $shippingServices;
    }

    private function checkPurchaseEnabled(): void
    {
        $settings = $this->contentService->getSettings();
        if (isset($settings['purchase_enabled']) && $settings['purchase_enabled'] === 'false') {
            throw new \Exception('Cálculo de frete temporariamente indisponível. Utilize o orçamento personalizado.');
        }
    }

    public function calculate(CalculateShippingRequest $request)
    {
        try {
            $this->checkPurchaseEnabled();
            
            $data = $request->validated();
            $cleanCep = preg_replace('/\D/', '', $data['destinationCep']);

            $validatedItems = [];
            foreach ($data['items'] as $item) {
                if ($item['quantity'] <= 0) throw new \Exception("Item {$item['productId']} possui quantidade inválida.");
                if ($item['quantity'] > 1000) throw new \Exception("Quantidade excessiva para o item {$item['productId']}.");

                $product = $this->productService->getById($item['productId']);
                if ($product) {
                    $validatedItems[] = [
                        'productId' => $product->id,
                        'weight' => $product->weight,
                        'width' => $product->width,
                        'height' => $product->height,
                        'length' => $product->length,
                        'quantity' => $item['quantity'],
                    ];
                }
            }

            if (empty($validatedItems)) return response()->json(['message' => 'Nenhum produto válido encontrado.'], 400);

            $allOptions = [];
            foreach ($this->shippingServices as $service) {
                $allOptions = array_merge($allOptions, $service->calculate($cleanCep, $validatedItems));
            }

            usort($allOptions, fn ($a, $b) => $a['price'] <=> $b['price']);

            return ShippingOptionResource::collection($allOptions);

        } catch (\Exception $e) {
            Log::error("Erro no cálculo de frete: " . $e->getMessage());
            return response()->json(['message' => $e->getMessage()], ($e->getCode() >= 400 && $e->getCode() < 600) ? $e->getCode() : 500);
        }
    }

    public function calculateForProduct(string $productId, string $cep)
    {
        try {
            $this->checkPurchaseEnabled();
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }

        $cleanCep = preg_replace('/\D/', '', $cep);
        if (strlen($cleanCep) !== 8) {
            return response()->json(['message' => 'CEP inválido. Informe apenas os 8 dígitos.'], 400);
        }

        try {
            $product = $this->productService->getById($productId);
            if (!$product) {
                return response()->json(['message' => 'Produto não encontrado.'], 404);
            }

            $item = [[
                'productId' => $product->id,
                'weight' => $product->weight,
                'width' => $product->width,
                'height' => $product->height,
                'length' => $product->length,
                'quantity' => 1,
            ]];

            $options = [];
            foreach ($this->shippingServices as $service) {
                /** @var ShippingServiceInterface $service */
                $options = array_merge($options, $service->calculate($cleanCep, $item));
            }

            usort($options, fn ($a, $b) => $a['price'] <=> $b['price']);
            return response()->json($options);
        } catch (\Throwable $e) {
            Log::error('Erro ao calcular frete por produto', [
                'productId' => $productId,
                'cep' => $cleanCep,
                'exception' => $e,
            ]);
            return response()->json(['message' => 'Erro ao calcular frete.'], 500);
        }
    }
}