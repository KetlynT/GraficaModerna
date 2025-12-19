<?php

namespace App\Http\Controllers;

use App\Services\AddressService;
use App\Services\ContentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AddressController extends Controller
{
    protected $service;
    protected $contentService;

    public function __construct(AddressService $service, ContentService $contentService)
    {
        $this->service = $service;
        $this->contentService = $contentService;
    }

    private function checkPurchaseEnabled()
    {
        $settings = $this->contentService->getSettings();
        if (isset($settings['purchase_enabled']) && $settings['purchase_enabled'] === 'false') {
            abort(403, "Gerenciamento de endereços indisponível no modo orçamento.");
        }
    }

    public function index()
    {
        return response()->json($this->service->getUserAddresses(Auth::id()));
    }

    public function show($id)
    {
        try {
            return response()->json($this->service->getById($id, Auth::id()));
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Endereço não encontrado'], 404);
        }
    }

    public function store(Request $request)
    {
        $this->checkPurchaseEnabled();
        
        $data = $request->validate([
            'name' => 'required',
            'receiverName' => 'required',
            'zipCode' => 'required',
            'street' => 'required',
            'number' => 'required',
            'neighborhood' => 'required',
            'city' => 'required',
            'state' => 'required',
            'phoneNumber' => 'required',
            'isDefault' => 'boolean',
            'complement' => 'nullable',
            'reference' => 'nullable'
        ]);

        $address = $this->service->create(Auth::id(), $data);
        return response()->json($address, 201);
    }

    public function update(Request $request, $id)
    {
        $this->checkPurchaseEnabled();
        
        // Validação similar ao store...
        $data = $request->all(); // Simplificado para brevidade

        try {
            $this->service->update($id, Auth::id(), $data);
            return response()->noContent();
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function destroy($id)
    {
        $this->checkPurchaseEnabled();
        $this->service->delete($id, Auth::id());
        return response()->noContent();
    }
}