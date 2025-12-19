<?php

namespace App\Http\Controllers;

use App\Services\AddressService;
use App\Services\ContentService;
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
        $this->middleware(['auth:api', 'throttle:user-actions']);

        $this->service = $service;
        $this->contentService = $contentService;
    }

    /**
     * Equivalente ao CheckPurchaseEnabled()
     */
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

    /**
     * GET api/addresses
     */
    public function index()
    {
        return response()->json(
            $this->service->getUserAddresses(Auth::id())
        );
    }

    /**
     * GET api/addresses/{id}
     */
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

    /**
     * POST api/addresses
     */
    public function store(Request $request)
    {
        try {
            $this->checkPurchaseEnabled();

            $data = $request->validate([
                'name'          => 'required|string|max:255',
                'receiverName'  => 'required|string|max:255',
                'zipCode'       => 'required|string|max:20',
                'street'        => 'required|string|max:255',
                'number'        => 'required|string|max:20',
                'neighborhood'  => 'required|string|max:255',
                'city'          => 'required|string|max:255',
                'state'         => 'required|string|max:2',
                'phoneNumber'   => 'required|string|max:20',
                'isDefault'     => 'boolean',
                'complement'    => 'nullable|string',
                'reference'     => 'nullable|string',
            ]);

            $created = $this->service->create(Auth::id(), $data);

            return response()->json($created, 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * PUT api/addresses/{id}
     */
    public function update(Request $request, string $id)
    {
        try {
            $this->checkPurchaseEnabled();

            $data = $request->validate([
                'name'          => 'sometimes|required|string|max:255',
                'receiverName'  => 'sometimes|required|string|max:255',
                'zipCode'       => 'sometimes|required|string|max:20',
                'street'        => 'sometimes|required|string|max:255',
                'number'        => 'sometimes|required|string|max:20',
                'neighborhood'  => 'sometimes|required|string|max:255',
                'city'          => 'sometimes|required|string|max:255',
                'state'         => 'sometimes|required|string|max:2',
                'phoneNumber'   => 'sometimes|required|string|max:20',
                'isDefault'     => 'boolean',
                'complement'    => 'nullable|string',
                'reference'     => 'nullable|string',
            ]);

            $this->service->update($id, Auth::id(), $data);

            return response()->noContent();
        } catch (ModelNotFoundException) {
            return response()->json(null, 404);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * DELETE api/addresses/{id}
     */
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
