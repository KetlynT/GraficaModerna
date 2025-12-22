<?php

namespace App\Http\Controllers;

use App\Services\AddressService;
use App\Services\ContentService;
use App\Http\Requests\AddressRequest;
use App\Http\Resources\AddressResource;
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
        $this->service = $service;
        $this->contentService = $contentService;
    }

    private function checkPurchaseEnabled(): void
    {
        $settings = $this->contentService->getSettings();

        if (isset($settings['purchase_enabled']) && $settings['purchase_enabled'] === 'false') {
            throw new \Exception('Gerenciamento de endereços indisponível no modo orçamento.');
        }
    }

    public function index()
    {
        $addresses = $this->service->getUserAddresses(Auth::id());
        return AddressResource::collection($addresses);
    }

    public function show(string $id)
    {
        try {
            $address = $this->service->getById($id, Auth::id());
            return new AddressResource($address);
        } catch (ModelNotFoundException) {
            return response()->json(null, 404);
        }
    }

    public function store(AddressRequest $request)
    {
        try {
            $this->checkPurchaseEnabled();

            $created = $this->service->create(Auth::id(), $request->validated());

            return response()->json(new AddressResource($created), 201);
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