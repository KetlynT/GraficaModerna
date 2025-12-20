<?php

namespace App\Application\Interfaces;

interface ITokenBlacklistService
{
    public function blacklist(string $token, int $expiryTimestamp): void;

    public function isBlacklisted(string $token): bool;
}