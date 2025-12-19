<?php
namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Facades\Cache;
use Illuminate\Pagination\LengthAwarePaginator;

class ProductService
{
    public function getCatalog(?string $search, ?string $sort, ?string $order, int $page, int $pageSize): LengthAwarePaginator
    {
        $cacheKey = "catalog_{$search}_{$sort}_{$order}_{$page}_{$pageSize}";

        // Cache por 15 segundos
        return Cache::remember($cacheKey, 15, function () use ($search, $sort, $order, $pageSize, $page) {
            $query = Product::where('is_active', true);

            if ($search) {
                $query->where('name', 'like', "%{$search}%");
            }

            if ($sort) {
                $query->orderBy($sort, $order === 'desc' ? 'desc' : 'asc');
            }

            return $query->paginate($pageSize, ['*'], 'page', $page);
        });
    }

    public function getById(string $id): Product
    {
        return Product::findOrFail($id);
    }

    public function create(array $data): Product
    {
        return Product::create($data);
    }

    public function update(string $id, array $data): void
    {
        $product = Product::findOrFail($id);
        $product->update($data);
    }

    public function delete(string $id): void
    {
        $product = Product::findOrFail($id);
        $product->deactivate(); // Soft delete lógico conforme seu código C#
    }
}