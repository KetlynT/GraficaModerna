<?php

namespace App\Http\Controllers;

use App\Services\ContentService;
use Illuminate\Http\Request;

class ContentController extends Controller
{
    protected $service;

    public function __construct(ContentService $service)
    {
        $this->service = $service;
    }

    // Público: Pegar configurações (banner, flags)
    public function getSettings()
    {
        return response()->json($this->service->getSettings());
    }

    // Público: Pegar página (termos, etc)
    public function getPage($slug)
    {
        try {
            return response()->json($this->service->getPageBySlug($slug));
        } catch (\Exception $e) {
            return response()->json(['message' => 'Página não encontrada'], 404);
        }
    }

    // Admin: Atualizar Configurações
    public function updateSettings(Request $request)
    {
        $data = $request->all();
        foreach ($data as $key => $value) {
            $this->service->updateSetting($key, $value);
        }
        return response()->noContent();
    }
    
    // Admin: Atualizar Páginas
    public function savePage(Request $request)
    {
        $data = $request->validate([
            'slug' => 'required',
            'title' => 'required',
            'content' => 'required'
        ]);
        
        $this->service->savePage($data);
        return response()->noContent();
    }
}