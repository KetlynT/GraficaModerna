<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class TokenBlacklistService
{
    // Adiciona o token na blacklist até a data de expiração dele
    public function blacklist(string $token, int $expiresInMinutes)
    {
        // Usamos o hash do token como chave para economizar espaço
        $key = 'blacklist_' . hash('sha256', $token);
        
        // Cache::put(chave, valor, tempo_em_minutos)
        Cache::put($key, true, $expiresInMinutes);
    }

    public function isBlacklisted(string $token): bool
    {
        $key = 'blacklist_' . hash('sha256', $token);
        return Cache::has($key);
    }
}