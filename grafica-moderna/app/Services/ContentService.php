<?php

namespace App\Services;

use App\Models\SiteSetting;
use App\Models\ContentPage;
use Illuminate\Support\Facades\Cache;

class ContentService
{
    // Retorna todas as configurações formatadas como objeto JSON simples
    public function getSettings()
    {
        return Cache::rememberForever('site_settings', function () {
            return SiteSetting::all()->pluck('value', 'key');
        });
    }

    public function updateSetting(string $key, $value)
    {
        SiteSetting::updateOrCreate(
            ['key' => $key],
            ['value' => (string)$value]
        );
        Cache::forget('site_settings');
    }

    public function getPageBySlug(string $slug)
    {
        return ContentPage::where('slug', $slug)->where('is_visible', true)->firstOrFail();
    }
    
    // Métodos administrativos
    public function savePage(array $data)
    {
        return ContentPage::updateOrCreate(
            ['slug' => $data['slug']],
            [
                'title' => $data['title'],
                'content' => $data['content'],
                'is_visible' => $data['isVisible'] ?? true
            ]
        );
    }
}