<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class User extends Authenticatable
{
    use HasUuids;

    protected $fillable = [
        'full_name', 
        'email', 
        'password', 
        'cpf_cnpj', 
        'phone_number',
        'refresh_token_hash', 
        'refresh_token_expiry'
    ];

    protected $hidden = [
        'password', 
        'refresh_token_hash'
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'lockout_end' => 'datetime',
    ];
}