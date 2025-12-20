<?php

namespace App\Http\Controllers;

use App\Services\ProductService;
use App\Http\Resources\ProductResource; // Importar o Resource
use Illuminate\Http\Request;

class ProductsController extends Controller
{
    protected ProductService $service;

    public function __construct(ProductService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request)
    {
        $search = $request->query('search');
        $sort = $request->query('sort');
        $order = $request->query('order');
        $page = max((int)$request->query('page', 1), 1);
        $pageSize = min((int)$request->query('pageSize', 8), 50); // C# limita a 50

        $paginator = $this->service->getCatalog(
            $search,
            $sort,
            $order,
            $page,
            $pageSize
        );

        // Transforma a coleção paginada usando o Resource para garantir camelCase
        // Isso gera uma estrutura { data: [...], meta: ..., links: ... }
        return ProductResource::collection($paginator);
    }

    public function show(string $id)
    {
        $product = $this->service->getById($id);
        return new ProductResource($product);
    }
}