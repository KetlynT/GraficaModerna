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
        // Cache Key idêntica à lógica do C# (sem minPrice/maxPrice)
        $cacheKey = "catalog_{$search}_{$sort}_{$order}_{$page}_{$pageSize}";

        // Cache de 15 segundos (igual ao C# TimeSpan.FromSeconds(15))
        return Cache::remember($cacheKey, 15, function () use ($search, $sort, $order, $pageSize, $page) {
            $query = Product::where('is_active', true);

            // 1. Busca (Réplica do ProductRepository.cs)
            if (!empty($search)) {
                $term = trim($search);
                // Sanitização manual para escapar caracteres especiais do LIKE, igual ao C#
                $sanitizedTerm = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $term);

                $query->where(function($q) use ($sanitizedTerm) {
                    $q->where('name', 'like', "%{$sanitizedTerm}%")
                      ->orWhere('description', 'like', "%{$sanitizedTerm}%");
                });
            }

            // 2. Ordenação (Restrita aos campos permitidos no C#)
            // C# allowedSortColumns: "price", "name", "stockquantity"
            $validSorts = [
                'price' => 'price',
                'name' => 'name',
                'stockquantity' => 'stock_quantity' // Mapeia DTO -> Coluna DB
            ];

            $sortLower = strtolower($sort ?? '');
            
            if (array_key_exists($sortLower, $validSorts)) {
                $column = $validSorts[$sortLower];
                $direction = strtolower($order ?? '') === 'desc' ? 'desc' : 'asc';
                $query->orderBy($column, $direction);
            } else {
                // Padrão C#: OrderByDescending(p => p.CreatedAt)
                $query->orderBy('created_at', 'desc');
            }

            return $query->paginate($pageSize, ['*'], 'page', $page);
        });
    }

    public function getById(string $id): Product
    {
        // C# lança KeyNotFoundException, Laravel lança ModelNotFoundException (404)
        return Product::where('id', $id)->where('is_active', true)->firstOrFail();
    }

    public function create(array $data): Product
    {
        // Mapeamento DTO -> Model deve ser feito no Controller ou aqui se os nomes diferirem.
        // Assumindo que o array $data já venha com as chaves corretas (snake_case) do Controller.
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