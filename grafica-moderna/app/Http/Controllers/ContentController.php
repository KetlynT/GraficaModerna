<?php

namespace App\Http\Controllers;

use App\Services\ContentService;
use Illuminate\Http\Request;

class ContentController extends Controller
{
    protected ContentService $service;

    public function __construct(ContentService $service)
    {
        $this->service = $service;
    }

    /**
     * GET /api/content/pages
     * Público – lista todas as páginas
     */
    public function getAllPages()
    {
        $pages = $this->service->getAllPages();
        return response()->json($pages);
    }

    /**
     * GET /api/content/pages/{slug}
     * Público – obtém página por slug
     */
    public function getPage(string $slug)
    {
        $page = $this->service->getPageBySlug($slug);

        if (!$page) {
            return response()->json(['message' => 'Página não encontrada'], 404);
        }

        return response()->json($page);
    }

    /**
     * GET /api/content/settings
     * Público – retorna configurações como dicionário key => value
     */
    public function getSettings()
    {
        $settingsList = $this->service->getSettings();

        // Garante o mesmo comportamento do ToDictionary do .NET
        $settingsDict = [];
        foreach ($settingsList as $setting) {
            $settingsDict[$setting['key']] = $setting['value'];
        }

        return response()->json($settingsDict);
    }
}
