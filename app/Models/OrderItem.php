<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    protected $fillable = [
        'order_id',
        'product_id',
        'product_name',
        'quantity',
        'unit_price',
        'refund_quantity'
    ];

    /**
     * Pertence a um Pedido.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Pertence a um Produto (Original).
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}