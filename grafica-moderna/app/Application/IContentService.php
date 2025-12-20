<?php

namespace App\Application\Interfaces;

use App\Models\ContentPage;

interface IContentService
{
    public function getPageBySlug(string $slug): ?ContentPage;

    /** @return ContentPage[] */
    public function getAllPages(): iterable;

    public function createPage(array $dto): ContentPage;

    public function updatePage(string $slug, array $dto): void;

    public function getSettings(): iterable;

    public function updateSettings(array $settings): void;
}