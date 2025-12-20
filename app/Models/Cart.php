<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Cart extends Model
{
    use HasUuids;

    protected $fillable = ['user_id', 'last_updated'];
    protected $casts = ['last_updated' => 'datetime'];

    public function items()
    {
        return $this->hasMany(CartItem::class);
    }
}