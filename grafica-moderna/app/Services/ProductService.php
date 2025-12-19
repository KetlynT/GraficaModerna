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
        ?float $minPrice, // Novo
        ?float $maxPrice, // Novo
        int $page, 
        int $pageSize
    ): LengthAwarePaginator
    {
        // Cache Key precisa incluir os novos filtros para não mostrar dados errados
        $cacheKey = "catalog_{$search}_{$sort}_{$order}_{$minPrice}_{$maxPrice}_{$page}_{$pageSize}";

        return Cache::remember($cacheKey, 15, function () use ($search, $sort, $order, $minPrice, $maxPrice, $pageSize, $page) {
            $query = Product::where('is_active', true);

            // 1. Busca Inteligente (Nome OU Descrição)
            if ($search) {
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
                });
            }

            // 2. Filtro de Preço
            if (!is_null($minPrice)) {
                $query->where('price', '>=', $minPrice);
            }
            if (!is_null($maxPrice)) {
                $query->where('price', '<=', $maxPrice);
            }

            // 3. Ordenação
            if ($sort) {
                // Mapeia termos do front para colunas do banco, se necessário
                $validSorts = ['price', 'name', 'created_at', 'sales_count'];
                if (in_array($sort, $validSorts)) {
                    $query->orderBy($sort, $order === 'desc' ? 'desc' : 'asc');
                }
            } else {
                // Padrão: Mais recentes primeiro
                $query->orderBy('created_at', 'desc');
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