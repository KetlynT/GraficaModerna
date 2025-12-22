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
        'role',
        'email_confirmed',
        'confirmation_token',
        'access_failed_count',
        'lockout_end',
        'refresh_token_hash', 
        'refresh_token_expiry'
    ];

    protected $hidden = [
        'password', 
        'refresh_token_hash',
        'confirmation_token'
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'lockout_end' => 'datetime',
        'refresh_token_expiry' => 'datetime',
        'email_confirmed' => 'boolean',
        'access_failed_count' => 'integer'
    ];
}