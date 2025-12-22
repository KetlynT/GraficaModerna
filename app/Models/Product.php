<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Product extends Model
{
    use HasUuids;

    protected $fillable = [
        'name', 'description', 'price', 'image_urls', 
        'weight', 'width', 'height', 'length', 'stock_quantity', 'is_active'
    ];

    protected $casts = [
        'image_urls' => 'array',
        'price' => 'decimal:2',
        'weight' => 'decimal:3',
        'is_active' => 'boolean',
    ];

    public function debitStock(int $quantity)
    {
        if ($quantity < 0) throw new \InvalidArgumentException("Quantidade inválida.");
        
        if ($this->stock_quantity < $quantity) {
            throw new \Exception("Estoque insuficiente para o produto '{$this->name}'.");
        }

        $this->decrement('stock_quantity', $quantity);
    }

    public function replenishStock(int $quantity)
    {
        $this->increment('stock_quantity', $quantity);
    }

    public function deactivate()
    {
        $this->update(['is_active' => false]);
    }
}