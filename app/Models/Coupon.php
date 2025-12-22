<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Coupon extends Model
{
    use HasUuids;

    protected $fillable = ['code', 'discount_percentage', 'expiry_date', 'is_active'];

    protected $casts = [
        'expiry_date' => 'datetime',
        'is_active' => 'boolean',
        'discount_percentage' => 'decimal:2'
    ];

    public function isValid(): bool
    {
        return $this->is_active && $this->expiry_date > now();
    }
}