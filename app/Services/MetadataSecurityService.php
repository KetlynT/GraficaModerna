<?php

namespace App\Services;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Contracts\Encryption\DecryptException;

class MetadataSecurityService
{
    public function protect(string $data): string
    {
        return Crypt::encryptString($data);
    }

    public function unprotect(string $encryptedData): string
    {
        try {
            return Crypt::decryptString($encryptedData);
        } catch (DecryptException $e) {
            throw new \Exception("Falha na integridade dos metadados.");
        }
    }
}