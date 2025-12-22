<?php

namespace App\Services;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Exception;

class SecurityService
{
    /**
     * Gera o hash da senha usando o Pepper ativo e versionamento.
     * Formato: $v1$HASH_BCRYPT
     */
    public function hashPassword(string $password): string
    {
        $activeVersion = config('services.security.active_version', 'v1');
        $pepper = config("services.security.peppers.{$activeVersion}");

        if (empty($activeVersion) || empty($pepper)) {
            Log::emergency("CRÍTICO: Configuração de segurança (Pepper) ausente ou inválida.");
            throw new Exception("Erro interno de configuração de segurança.");
        }

        $hash = Hash::make($password . $pepper);

        return "\${$activeVersion}\${$hash}";
    }

    /**
     * Verifica se a senha corresponde ao hash, suportando múltiplas versões de Pepper.
     */
    public function verifyPassword(string $providedPassword, string $storedHash): bool
    {
        $version = 'v1';
        $actualHash = $storedHash;

        if (str_starts_with($storedHash, '$v')) {
            $parts = explode('$', $storedHash);
            if (count($parts) >= 3) {
                $version = $parts[1];
                $actualHash = implode('$', array_slice($parts, 2));
            }
        }

        $pepper = config("services.security.peppers.{$version}");

        if (empty($pepper)) {
            Log::warning("Falha no Login: Pepper da versão '{$version}' não encontrado.");
            return false;
        }

        return Hash::check($providedPassword . $pepper, $actualHash);
    }

    /**
     * Verifica se o hash precisa ser atualizado (Rehash)
     * Ex: Se mudou o algoritmo ou a versão do Pepper ativa.
     */
    public function needsRehash(string $storedHash): bool
    {
        $activeVersion = config('services.security.active_version', 'v1');
        
        if (!str_starts_with($storedHash, "\${$activeVersion}\$")) {
            return true;
        }

        $parts = explode('$', $storedHash);
        $realHash = (count($parts) >= 3) ? implode('$', array_slice($parts, 2)) : $storedHash;
        
        $pepper = config("services.security.peppers.{$activeVersion}");
        return Hash::needsRehash($realHash, ['rounds' => 10]);
    }
}