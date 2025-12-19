<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Laravel\Sanctum\HasApiTokens; // Recomendado para Tokens API

class User extends Authenticatable
{
    use HasApiTokens, HasUuids, Notifiable;

    protected $fillable = [
        'full_name', 'email', 'password', 'cpf_cnpj', 'phone_number', 'role'
    ];

    protected $hidden = [
        'password', 'remember_token', 'refresh_token_hash'
    ];
}