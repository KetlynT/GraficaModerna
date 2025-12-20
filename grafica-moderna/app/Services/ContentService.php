<?php

namespace App\Services;

use App\Models\SiteSetting;
use App\Models\ContentPage;
use Illuminate\Support\Facades\Cache;

class ContentService
{
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

    // Adicionado para compatibilidade com AdminController
    public function updateSettings(array $settings)
    {
        foreach ($settings as $key => $value) {
            $this->updateSetting($key, $value);
        }
    }

    public function getPageBySlug(string $slug)
    {
        return ContentPage::where('slug', $slug)->where('is_visible', true)->firstOrFail();
    }
    
    // Adicionado: createPage chama savePage
    public function createPage(array $data)
    {
        return $this->savePage($data);
    }

    // Adicionado: updatePage chama savePage preservando o slug se necessário
    public function updatePage(string $slug, array $data)
    {
        // Garante que estamos atualizando o slug correto
        $data['slug'] = $slug; 
        return $this->savePage($data);
    }

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