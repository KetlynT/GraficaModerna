<?php

namespace App\Application\Interfaces;

interface IProductService
{
    /**
     * @return array{items: array, total: int, page: int, pageSize: int}
     */
    public function getCatalog(
        ?string $search, 
        ?string $sort, 
        ?string $order, 
        int $page,
        int $pageSize
    ): array;

    public function getById(string $id): ?object;

    public function create(array $dto): object;

    public function update(string $id, array $dto): void;

    public function delete(string $id): void;
}