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

        $result = $this->service->getCatalog(
            $request->input('search'),
            $request->input('sort'),
            $request->input('order'),
            $request->input('minPrice'), // Recebe ?minPrice=10
            $request->input('maxPrice'), // Recebe ?maxPrice=100
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