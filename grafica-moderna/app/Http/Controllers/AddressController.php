<?php

namespace App\Http\Controllers;

use App\Services\AddressService;
use App\Services\ContentService;
use App\Http\Requests\Address\AddressRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class AddressController extends Controller
{
    protected AddressService $service;
    protected ContentService $contentService;

    public function __construct(
        AddressService $service,
        ContentService $contentService
    ) {
        // Middlewares aplicados nas rotas
        $this->service = $service;
        $this->contentService = $contentService;
    }

    private function checkPurchaseEnabled(): void
    {
        $settings = $this->contentService->getSettings();

        if (
            isset($settings['purchase_enabled']) &&
            $settings['purchase_enabled'] === 'false'
        ) {
            abort(
                400,
                'Gerenciamento de endereços indisponível no modo orçamento.'
            );
        }
    }

    public function index()
    {
        return response()->json(
            $this->service->getUserAddresses(Auth::id())
        );
    }

    public function show(string $id)
    {
        try {
            return response()->json(
                $this->service->getById($id, Auth::id())
            );
        } catch (ModelNotFoundException) {
            return response()->json(null, 404);
        }
    }

    public function store(AddressRequest $request)
    {
        try {
            $this->checkPurchaseEnabled();

            // Validação via AddressRequest
            $created = $this->service->create(Auth::id(), $request->validated());

            return response()->json($created, 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function update(AddressRequest $request, string $id)
    {
        try {
            $this->checkPurchaseEnabled();

            // Validação via AddressRequest
            $this->service->update($id, Auth::id(), $request->validated());

            return response()->noContent();
        } catch (ModelNotFoundException) {
            return response()->json(null, 404);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function destroy(string $id)
    {
        try {
            $this->checkPurchaseEnabled();

            $this->service->delete($id, Auth::id());

            return response()->noContent();
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 400);
        }
    }
}