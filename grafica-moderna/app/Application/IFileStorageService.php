<?php

namespace App\Application\Interfaces;

use Illuminate\Http\UploadedFile;

interface IFileStorageService
{
    public function saveFile(UploadedFile $file, string $folderName): string;

    public function deleteFile(string $fileUrl): void;
}