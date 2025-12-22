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

    public function getAllPages()
    {
        $pages = $this->service->getAllPages();
        return response()->json($pages);
    }

    public function getPage(string $slug)
    {
        $page = $this->service->getPageBySlug($slug);

        if (!$page) {
            return response()->json(['message' => 'Página não encontrada'], 404);
        }

        return response()->json($page);
    }

    public function getSettings()
    {
        $settingsList = $this->service->getSettings();

        $settingsDict = [];
        foreach ($settingsList as $setting) {
            $settingsDict[$setting['key']] = $setting['value'];
        }

        return response()->json($settingsDict);
    }
}