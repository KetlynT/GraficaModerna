<?php

namespace App\Application\Interfaces;

interface IAuthService
{
    /**
     * @return object Contém accessToken, refreshToken, role, email
     */
    public function register(array $data): object;

    public function login(array $data): object;

    public function refreshToken(array $tokenModel): object;

    public function getProfile(string $userId): object;

    public function updateProfile(string $userId, array $data): void;

    public function confirmEmail(array $dto): void;

    public function forgotPassword(array $dto): void;

    public function resetPassword(array $dto): void;
}