<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\RefundRequest;
use App\Models\RefundRequestItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrdersController extends Controller
{
    public function index(Request $request)
    {
        $orders = Order::where('user_id', auth()->id())
            ->with(['items.product', 'refundRequests']) 
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('pageSize', 10));

        if ($request->wantsJson()) {
            return response()->json($orders);
        }

        return view('profile.orders.index', compact('orders'));
    }

    public function show($id)
    {
        $order = Order::with(['items.product', 'address', 'refundRequests.items'])
            ->where('user_id', auth()->id())
            ->where('id', $id)
            ->firstOrFail();

        return response()->json($order);
    }

    public function checkout(Request $request)
    {
        return response()->json(['message' => 'Stub checkout']);
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

        $allowedStatus = ['Pago', 'Entregue', 'delivered', 'paid'];
        
        if (!in_array($order->status, $allowedStatus)) {
            return response()->json(['message' => 'Status inválido para solicitação.'], 400);
        }

        return DB::transaction(function () use ($request, $order, $user) {
            
            $refundRequest = RefundRequest::create([
                'order_id' => $order->id,
                'user_id' => $user->id,
                'reason' => $request->reason,
                'status' => 'pending'
            ]);

            foreach ($request->items as $itemData) {
                $orderItem = $order->items()->where('id', $itemData['order_item_id'])->firstOrFail();
                
                if ($itemData['quantity'] > $orderItem->quantity) {
                    throw new \Exception("Quantidade solicitada maior que a comprada.");
                }

                RefundRequestItem::create([
                    'refund_request_id' => $refundRequest->id,
                    'order_item_id' => $orderItem->id,
                    'quantity_requested' => $itemData['quantity']
                ]);
            }

            $order->status = 'Em Análise de Reembolso';
            $order->save();

            return response()->json([
                'message' => 'Solicitação enviada com sucesso.',
                'refund_request' => $refundRequest
            ]);
        });
    }
}