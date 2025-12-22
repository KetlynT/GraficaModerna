<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class TokenBlacklistService
{
    public function blacklist(string $token, int $expiresAtTimestamp): void
    {
        $ttl = $expiresAtTimestamp - time();
        
        if ($ttl > 0) {
            Cache::put("jwt_blacklist_{$token}", true, $ttl);
        }
    }

    public function isBlacklisted(string $token): bool
    {
        return Cache::has("jwt_blacklist_{$token}");
    }
}