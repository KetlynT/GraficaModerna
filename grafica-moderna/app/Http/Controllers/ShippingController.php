<?php

namespace App\Http\Controllers;

use App\Services\ContentService;
use App\Services\ProductService;
use App\Services\Shipping\ShippingServiceInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ShippingController extends Controller
{
    private const MAX_ITEMS_PER_CALCULATION = 50;

    public function __construct(
        protected iterable $shippingServices,
        protected ProductService $productService,
        protected ContentService $contentService
    ) {}

    protected function checkPurchaseEnabled(): void
    {
        $settings = $this->contentService->getSettings();
        if (($settings['purchase_enabled'] ?? 'true') === 'false') {
            throw new \Exception(
                'Cálculo de frete temporariamente indisponível. Utilize o orçamento personalizado.'
            );
        }
    }

    public function calculate(Request $request)
    {
        try {
            $this->checkPurchaseEnabled();
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }

        $data = $request->validate([
            'destinationCep' => 'required|string',
            'items' => 'required|array|min:1|max:' . self::MAX_ITEMS_PER_CALCULATION,
            'items.*.productId' => 'required|uuid',
            'items.*.quantity' => 'required|integer|min:1|max:1000',
        ]);

        $cep = preg_replace('/\D/', '', $data['destinationCep']);
        if (strlen($cep) !== 8) {
            return response()->json(['message' => 'CEP inválido. Certifique-se de informar os 8 dígitos.'], 400);
        }

        $validatedItems = [];

        foreach ($data['items'] as $item) {
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

        if (empty($validatedItems)) {
            return response()->json(['message' => 'Nenhum produto válido encontrado para cálculo.'], 400);
        }

        try {
            $options = [];
            foreach ($this->shippingServices as $service) {
                /** @var ShippingServiceInterface $service */
                $options = array_merge($options, $service->calculate($cep, $validatedItems));
            }

            usort($options, fn ($a, $b) => $a['price'] <=> $b['price']);
            return response()->json($options);
        } catch (\Throwable $e) {
            Log::error('Erro crítico ao calcular frete', ['cep' => $cep, 'exception' => $e]);
            return response()->json(['message' => 'Erro ao calcular frete.'], 500);
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
