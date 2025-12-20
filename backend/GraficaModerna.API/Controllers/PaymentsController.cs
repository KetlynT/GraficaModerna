<?php

namespace App\Http\Controllers;

use App\Services\PaymentService;
use App\Services\OrderService;
use App\Services\ContentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    protected PaymentService $paymentService;
    protected OrderService $orderService;
    protected ContentService $contentService;

    public function __construct(
        PaymentService $paymentService,
        OrderService $orderService,
        ContentService $contentService
    ) {
        $this->middleware('auth:api');
        $this->paymentService = $paymentService;
        $this->orderService = $orderService;
        $this->contentService = $contentService;
    }

    /**
     * Verifica se pagamentos estão habilitados
     */
    private function checkPurchaseEnabled(): void
    {
        $settings = $this->contentService->getSettings();

        if (isset($settings['purchase_enabled']) && $settings['purchase_enabled'] === 'false') {
            throw new \RuntimeException('Pagamentos estão desativados temporariamente.');
        }
    }

    /**
     * POST /api/payments/checkout-session/{orderId}
     * Cria sessão de pagamento
     */
    public function createSession(string $orderId)
    {
        try {
            $this->checkPurchaseEnabled();
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }

        $userId = Auth::id();
        if (!$userId) {
            Log::warning('Tentativa de criar sessão de pagamento sem userId válido');
            return response()->json(['message' => 'Usuário não identificado.'], 401);
        }

        try {
            $order = $this->orderService->getOrderForPayment($orderId, $userId);
            $url = $this->paymentService->createCheckoutSession($order);

            Log::info('Sessão de pagamento criada com sucesso.', ['orderId' => $orderId]);

            return response()->json(['url' => $url]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::warning('Pedido não encontrado para pagamento.', ['orderId' => $orderId]);
            return response()->json(['message' => 'Pedido não encontrado.'], 404);
        } catch (\InvalidArgumentException $e) {
            Log::warning('Erro de validação do pedido.', ['message' => $e->getMessage()]);
            return response()->json(['message' => $e->getMessage()], 400);
        } catch (\Throwable $e) {
            Log::error('Erro ao processar pagamento.', [
                'orderId' => $orderId,
                'exception' => $e,
            ]);

            return response()->json([
                'message' => 'Erro ao processar pagamento. Tente novamente em alguns instantes.'
            ], 500);
        }
    }

    /**
     * GET /api/payments/status/{orderId}
     * Retorna status do pagamento
     */
    public function getPaymentStatus(string $orderId)
    {
        $userId = Auth::id();

        try {
            $status = $this->orderService->getPaymentStatus($orderId, $userId);
            return response()->json($status);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Pedido não encontrado.'], 404);
        }
    }
}
