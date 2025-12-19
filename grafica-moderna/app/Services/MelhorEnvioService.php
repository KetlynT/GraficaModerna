<?php

namespace App\Services\Shipping;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Exception;

class MelhorEnvioService
{
    protected $baseUrl;
    protected $token;
    protected $fromPostalCode;

    public function __construct()
    {
        $this->baseUrl = config('services.melhorenvio.url', 'https://www.melhorenvio.com.br/api/v2');
        $this->token = config('services.melhorenvio.token');
        $this->fromPostalCode = config('services.melhorenvio.from_zip', '15700000'); // CEP da Gráfica (Jales-SP)
    }

    public function calculateShipping(string $toPostalCode, array $items)
    {
        // Simulação de resposta caso não tenha token configurado (para desenvolvimento)
        if (empty($this->token) || config('app.env') === 'local') {
            return $this->getMockShipping($toPostalCode);
        }

        // Montagem do Payload real da API
        $payload = [
            'from' => [
                'postal_code' => $this->fromPostalCode
            ],
            'to' => [
                'postal_code' => $toPostalCode
            ],
            'products' => array_map(function ($item) {
                return [
                    'id' => $item->product_id, // ou SKU
                    'width' => $item->product->width ?? 10,
                    'height' => $item->product->height ?? 10,
                    'length' => $item->product->length ?? 10,
                    'weight' => $item->product->weight ?? 0.5,
                    'insurance_value' => $item->product->price,
                    'quantity' => $item->quantity
                ];
            }, $items)
        ];

        try {
            $response = Http::withToken($this->token)
                ->withHeaders(['Accept' => 'application/json'])
                ->post("{$this->baseUrl}/me/shipment/calculate", $payload);

            if ($response->failed()) {
                throw new Exception("Erro na API de frete: " . $response->body());
            }

            return $this->formatResponse($response->json());

        } catch (Exception $e) {
            // Fallback ou log de erro
            throw new Exception("Serviço de cálculo de frete indisponível.");
        }
    }

    private function formatResponse(array $services)
    {
        // Filtra apenas PAC e SEDEX (Correios) ou Jadlog
        $allowedServices = ['SEDEX', 'PAC', '.Com', 'Jadlog Package'];
        
        $options = [];
        foreach ($services as $service) {
            if (isset($service['error'])) continue;
            
            if (in_array($service['name'], $allowedServices) || in_array($service['company']['name'], $allowedServices)) {
                $options[] = [
                    'name' => $service['name'] . ' (' . $service['company']['name'] . ')',
                    'price' => (float) $service['price'],
                    'currency' => $service['currency'] ?? 'BRL',
                    'delivery_time' => (int) $service['delivery_time'], // Dias úteis
                    'company_logo' => $service['company']['picture'] ?? null
                ];
            }
        }
        
        // Ordena pelo preço menor
        usort($options, fn($a, $b) => $a['price'] <=> $b['price']);
        
        return $options;
    }

    // Mock para desenvolvimento sem API Key
    private function getMockShipping($cep)
    {
        // Preços fictícios baseados no CEP
        $basePrice = (intval(substr($cep, 0, 2)) * 0.5) + 15.00; 

        return [
            [
                'name' => 'PAC (Correios)',
                'price' => $basePrice,
                'delivery_time' => 7,
                'company_logo' => 'https://melhorenvio.com.br/images/shipping-companies/correios.png'
            ],
            [
                'name' => 'SEDEX (Correios)',
                'price' => $basePrice * 1.8,
                'delivery_time' => 2,
                'company_logo' => 'https://melhorenvio.com.br/images/shipping-companies/correios.png'
            ],
            [
                'name' => 'Jadlog Package',
                'price' => $basePrice * 0.9,
                'delivery_time' => 5,
                'company_logo' => 'https://melhorenvio.com.br/images/shipping-companies/jadlog.png'
            ]
        ];
    }
}