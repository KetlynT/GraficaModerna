<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class UserAddress extends Model
{
    use HasUuids;

    protected $fillable = [
        'user_id', 'name', 'receiver_name', 'zip_code', 'street', 'number',
        'complement', 'neighborhood', 'city', 'state', 'reference', 
        'phone_number', 'is_default'
    ];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    // Accessor para ToString (equivalente ao C#)
    public function getFullDescriptionAttribute()
    {
        return "{$this->street}, {$this->number} - {$this->complement} - {$this->neighborhood}, {$this->city}/{$this->state} - CEP: {$this->zip_code} (Ref: {$this->reference}) - A/C: {$this->receiver_name} - Tel: {$this->phone_number}";
    }
}