<?php
namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Facades\Cache;
use Illuminate\Pagination\LengthAwarePaginator;

class ProductService
{
    public function getCatalog(
        ?string $search, 
        ?string $sort, 
        ?string $order, 
        int $page, 
        int $pageSize
    ): LengthAwarePaginator
    {
        $cacheKey = "catalog_{$search}_{$sort}_{$order}_{$page}_{$pageSize}";

        return Cache::remember($cacheKey, 15, function () use ($search, $sort, $order, $pageSize, $page) {
            $query = Product::where('is_active', true);

            if (!empty($search)) {
                $term = trim($search);
                $sanitizedTerm = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $term);

                $query->where(function($q) use ($sanitizedTerm) {
                    $q->where('name', 'like', "%{$sanitizedTerm}%")
                      ->orWhere('description', 'like', "%{$sanitizedTerm}%");
                });
            }

            $validSorts = [
                'price' => 'price',
                'name' => 'name',
                'stockquantity' => 'stock_quantity'
            ];

            $sortLower = strtolower($sort ?? '');
            
            if (array_key_exists($sortLower, $validSorts)) {
                $column = $validSorts[$sortLower];
                $direction = strtolower($order ?? '') === 'desc' ? 'desc' : 'asc';
                $query->orderBy($column, $direction);
            } else {
                $query->orderBy('created_at', 'desc');
            }

            return $query->paginate($pageSize, ['*'], 'page', $page);
        });
    }

    public function getById(string $id): Product
    {
        return Product::where('id', $id)->where('is_active', true)->firstOrFail();
    }

    public function create(array $data): Product
    {
        return Product::create($data);
    }

    public function update(string $id, array $data): void
    {
        $product = $this->getById($id);
        $product->update($data);
    }

    public function delete(string $id): void
    {
        $product = $this->getById($id);
        $product->deactivate();
    }
}