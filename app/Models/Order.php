<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Order extends Model
{
    use HasUuids;

    protected $fillable = [
    'user_id', 'order_date', 'delivery_date', 'sub_total', 'discount', 
    'shipping_cost', 'shipping_method', 'total_amount', 'applied_coupon', 
    'status', 'tracking_code', 'reverse_logistics_code', 'return_instructions',
    'refund_type', 'refund_requested_amount', 'refund_rejection_reason', 
    'refund_rejection_proof', 'shipping_address', 'shipping_zip_code', 
    'customer_ip', 'user_agent', 'stripe_payment_intent_id'
];

    protected $casts = [
        'order_date' => 'datetime',
        'delivery_date' => 'datetime',
        'sub_total' => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];

    // Relacionamento com User
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Relacionamento com Items (equivalente a List<OrderItem>)
    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    // Relacionamento com History (equivalente a List<OrderHistory>)
    public function history()
    {
        return $this->hasMany(OrderHistory::class);
    }
}