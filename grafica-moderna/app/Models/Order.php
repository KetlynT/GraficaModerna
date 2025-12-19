<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Order extends Model
{
    use HasUuids;

    protected $fillable = [
        'user_id', 'order_date', 'sub_total', 'discount', 'shipping_cost',
        'shipping_method', 'total_amount', 'applied_coupon', 'status',
        'shipping_address', 'shipping_zip_code', 'customer_ip', 'user_agent'
        // ... adicione os outros campos aqui
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