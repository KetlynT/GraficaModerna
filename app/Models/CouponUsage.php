<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class CouponUsage extends Model
{
    use HasUuids;
    public $timestamps = false;

    protected $fillable = ['user_id', 'coupon_code', 'order_id', 'used_at'];
    
    protected $casts = ['used_at' => 'datetime'];
}