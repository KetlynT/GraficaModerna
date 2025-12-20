<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'orderNumber' => $this->order_number,
            'orderDate' => $this->order_date,
            'deliveryDate' => $this->delivery_date,
            'subTotal' => (float) $this->sub_total,
            'discount' => (float) $this->discount,
            'address' => new AddressResource($this->whenLoaded('address')),
            'shippingCost' => (float) $this->shipping_cost,
            'totalAmount' => (float) $this->total_amount,
            'status' => $this->status,
            'trackingCode' => $this->tracking_code,
            'reverseLogisticsCode' => $this->reverse_logistics_code,
            'returnInstructions' => $this->return_instructions,
            'refundRejectionReason' => $this->refund_rejection_reason,
            'refundRejectionProof' => $this->refund_rejection_proof,
            'shippingAddress' => $this->shipping_address,
            'customerName' => $this->user->full_name ?? 'Cliente',
            'items' => OrderItemResource::collection($this->items),
            'paymentWarning' => $this->when($this->payment_warning, $this->payment_warning),
        ];
    }
}