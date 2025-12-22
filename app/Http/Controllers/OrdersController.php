<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\RefundRequest;
use App\Models\RefundRequestItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrdersController extends Controller
{
    public function checkout(Request $request)
    {
        return response()->json(['message' => 'Stub checkout']);
    }

    public function index(Request $request)
    {
        $orders = Order::where('user_id', auth()->id())
            ->with(['refundRequests']) 
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('pageSize', 10));

        return response()->json($orders);
    }

    public function show($id)
    {
        $order = Order::with(['items.product', 'address', 'refundRequests.items'])
            ->where('user_id', auth()->id())
            ->where('id', $id)
            ->firstOrFail();

        return response()->json($order);
    }

    public function requestRefund(Request $request, $id)
    {
        $request->validate([
            'reason' => 'required|string|min:10',
            'items'  => 'required|array|min:1',
            'items.*.order_item_id' => 'required|integer',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        $user = auth()->user();
        $order = Order::where('id', $id)->where('user_id', $user->id)->firstOrFail();

        if (!in_array($order->status, ['paid', 'delivered'])) {
            return response()->json(['message' => 'Status inválido para reembolso.'], 400);
        }

        return DB::transaction(function () use ($request, $order, $user) {
            
            $refundRequest = RefundRequest::create([
                'order_id' => $order->id,
                'user_id' => $user->id,
                'reason' => $request->reason,
                'status' => 'pending'
            ]);

            foreach ($request->items as $itemData) {
                // Valida se o item pertence ao pedido
                $orderItem = $order->items()->where('id', $itemData['order_item_id'])->firstOrFail();
                
                if ($itemData['quantity'] > $orderItem->quantity) {
                    throw new \Exception("Quantidade solicitada maior que a comprada para o item #{$orderItem->id}");
                }

                RefundRequestItem::create([
                    'refund_request_id' => $refundRequest->id,
                    'order_item_id' => $orderItem->id,
                    'quantity_requested' => $itemData['quantity']
                ]);
            }

            // Atualiza status global do pedido apenas visualmente
            $order->status = 'refund_processing';
            $order->save();

            return response()->json([
                'message' => 'Solicitação de reembolso criada com sucesso.',
                'refund_request' => $refundRequest->load('items')
            ]);
        });
    }
}