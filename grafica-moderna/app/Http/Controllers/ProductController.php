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
    public function store(Request $request)
{
    // Validação
    $data = $request->validate([
        'name' => 'required',
        'description' => 'required',
        'price' => 'required|numeric',
        'images.*' => 'image|mimes:jpeg,png,jpg,webp|max:2048', // Valida array de imagens
        // ... outros campos
    ]);

    $imageUrls = [];
    if ($request->hasFile('images')) {
        foreach ($request->file('images') as $image) {
            // Salva em storage/app/public/products e retorna o caminho
            $path = $image->store('products', 'public');
            $imageUrls[] = config('app.url') . '/storage/' . $path;
        }
    }

    $data['image_urls'] = $imageUrls;

    try {
        $product = $this->service->create($data);
        return response()->json($product, 201);
    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 400);
    }
}
}