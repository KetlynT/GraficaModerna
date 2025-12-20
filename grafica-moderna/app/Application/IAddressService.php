<?php

namespace App\Application\Interfaces;

interface IAddressService
{
    /**
     * @return object[] Lista de endereços
     */
    public function getUserAddresses(string $userId): array;

    public function getById(string $id, string $userId): ?object;

    public function create(string $userId, array $data): object;

    public function update(string $id, string $userId, array $data): void;

    public function delete(string $id, string $userId): void;
}