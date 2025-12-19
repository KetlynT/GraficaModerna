<?php

namespace App\Http\Controllers;

use App\Services\ProductService;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    protected $service;

    public function __construct(ProductService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request)
    {
        $page = $request->input('page', 1);
        $pageSize = $request->input('pageSize', 8);

        // Validação simples
        $pageSize = min($pageSize, 50);
        $page = max($page, 1);

        $result = $this->service->getCatalog(
            $request->input('search'),
            $request->input('sort'),
            $request->input('order'),
            $page,
            $pageSize
        );

        return response()->json($result);
    }

    public function show($id)
    {
        try {
            $product = $this->service->getById($id);
            return response()->json($product);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Produto não encontrado.'], 404);
        }
    }

}