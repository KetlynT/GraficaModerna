<?php

namespace App\Http\Controllers;

use App\Services\ProductService;
use Illuminate\Http\Request;

class ProductsController extends Controller
{
    protected ProductService $service;

    public function __construct(ProductService $service)
    {
        $this->service = $service;
    }

    /**
     * GET /api/products
     */
    public function index(Request $request)
    {
        $search = $request->query('search');
        $sort = $request->query('sort');
        $order = $request->query('order');
        $page = max((int)$request->query('page', 1), 1);
        $pageSize = min((int)$request->query('pageSize', 8), 50);

        $result = $this->service->getCatalog(
            $search,
            $sort,
            $order,
            $page,
            $pageSize
        );

        return response()->json($result);
    }

    /**
     * GET /api/products/{id}
     */
    public function show(string $id)
    {
        $product = $this->service->getById($id);

        if (!$product) {
            return response()->json(['message' => 'Produto não encontrado.'], 404);
        }

        return response()->json($product);
    }
}