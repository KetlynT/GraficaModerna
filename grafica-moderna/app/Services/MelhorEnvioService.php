<?php

namespace App\Services;

use App\Models\SiteSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class MelhorEnvioService
{
    protected $baseUrl;
    protected $userAgent;

    public function __construct()
    {
        $this->baseUrl = config('services.melhorenvio.url', 'https://www.melhorenvio.com.br/api/v2');
        $this->userAgent = config('services.melhorenvio.user_agent', 'GraficaModernaAPI/1.0 (suporte@graficamoderna.com.br)');
    }

    public function calculate(string $destinationCep, array $items): array
    {
        if (empty($items)) return [];

        $originCepSetting = SiteSetting::where('key', 'sender_cep')->first();
        $originCep = $originCepSetting ? str_replace('-', '', trim($originCepSetting->value)) : null;

        if (empty($originCep)) {
            throw new Exception("CEP de origem não configurado. Entre em contato com a administração.");
        }

        $payload = [
            'from' => ['postal_code' => $originCep],
            'to' => ['postal_code' => str_replace('-', '', trim($destinationCep))],
            'products' => array_map(fn($i) => [
                'width' => $i['width'],
                'height' => $i['height'],
                'length' => $i['length'],
                'weight' => $i['weight'],
                'insurance_value' => 0,
                'quantity' => $i['quantity']
            ], $items)
        ];

        try {
            $maxRetries = 3;
            $attempt = 0;
            $response = null;

            while ($attempt < $maxRetries) {
                try {
                    $token = $this->getAccessToken();
                    
                    $response = Http::withToken($token)
                        ->withHeaders([
                            'Accept' => 'application/json',
                            'User-Agent' => $this->userAgent
                        ])
                        ->post("{$this->baseUrl}/me/shipment/calculate", $payload);

                    /** @var \Illuminate\Http\Client\Response $response */
                    if ($response->status() === 401) {
                        $newToken = $this->refreshAccessToken();
                        if ($newToken) {
                            $response = Http::withToken($newToken)
                                ->withHeaders([
                                    'Accept' => 'application/json',
                                    'User-Agent' => $this->userAgent
                                ])
                                ->post("{$this->baseUrl}/me/shipment/calculate", $payload);
                        }
                    }

                    /** @var \Illuminate\Http\Client\Response $response */
                    if ($response->successful() || $response->status() < 500) {
                        break;
                    }

                    throw new Exception("Erro de servidor: " . $response->status());

                } catch (Exception $ex) {
                    $attempt++;
                    if ($attempt >= $maxRetries) throw $ex;
                    sleep(1 * $attempt);
                }
            }

            /** @var \Illuminate\Http\Client\Response $response */
            if (!$response || !$response->successful()) {
                Log::error("Erro API Melhor Envio ({$response?->status()}): " . $response?->body());
                return [];
            }

            $json = $response->json();
            return $this->formatResponse($json);

        } catch (Exception $ex) {
            Log::error("Erro crítico ao calcular frete Melhor Envio: " . $ex->getMessage());
            return [];
        }
    }

    private function getAccessToken(): string
    {
        $dbToken = SiteSetting::where('key', 'melhor_envio_access_token')->first();
        if ($dbToken && !empty($dbToken->value)) {
            return $dbToken->value;
        }
        return config('services.melhorenvio.token', '');
    }

    private function refreshAccessToken(): ?string
    {
        try {
            $clientId = config('services.melhorenvio.client_id');
            $clientSecret = config('services.melhorenvio.client_secret');
            
            $dbRefreshToken = SiteSetting::where('key', 'melhor_envio_refresh_token')->first();
            $refreshToken = $dbRefreshToken ? $dbRefreshToken->value : config('services.melhorenvio.refresh_token');

            if (empty($clientId) || empty($clientSecret) || empty($refreshToken)) {
                return null;
            }

            $response = Http::asForm()->post("{$this->baseUrl}/oauth/token", [
                'grant_type' => 'refresh_token',
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'refresh_token' => $refreshToken
            ]);

            /** @var \Illuminate\Http\Client\Response $response */
            if (!$response->successful()) {
                return null;
            }

            $data = $response->json();
            
            $this->updateTokenInDb('melhor_envio_access_token', $data['access_token'] ?? null);
            $this->updateTokenInDb('melhor_envio_refresh_token', $data['refresh_token'] ?? null);

            return $data['access_token'] ?? null;

        } catch (Exception $ex) {
            return null;
        }
    }

    private function updateTokenInDb(string $key, ?string $value)
    {
        if (empty($value)) return;
        SiteSetting::updateOrCreate(['key' => $key], ['value' => $value]);
    }

    private function formatResponse(array $data): array
    {
        $options = [];
        foreach ($data as $item) {
            if (!empty($item['error'])) continue;

            $price = 0;
            if (isset($item['price'])) $price = (float) $item['price'];
            elseif (isset($item['custom_price'])) $price = (float) $item['custom_price'];

            if ($price <= 0) continue;

            $deliveryDays = $item['delivery_range']['max'] ?? $item['delivery_time'];

            $options[] = [
                'name' => ($item['company']['name'] ?? '') . ' - ' . ($item['name'] ?? ''),
                'price' => $price,
                'deliveryDays' => (int) $deliveryDays,
                'provider' => 'Melhor Envio'
            ];
        }

        usort($options, fn($a, $b) => $a['price'] <=> $b['price']);
        return $options;
    }
}