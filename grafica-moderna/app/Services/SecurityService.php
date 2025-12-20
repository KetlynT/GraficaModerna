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

        // Concatena senha + pepper (Igual ao C#)
        $hash = Hash::make($password . $pepper);

        // Adiciona prefixo de versão
        return "\${$activeVersion}\${$hash}";
    }

    /**
     * Verifica se a senha corresponde ao hash, suportando múltiplas versões de Pepper.
     */
    public function verifyPassword(string $providedPassword, string $storedHash): bool
    {
        // 1. Extrair versão e hash real
        $version = 'v1'; // Default
        $actualHash = $storedHash;

        // Verifica se tem o prefixo de versão (ex: $v1$...)
        if (str_starts_with($storedHash, '$v')) {
            $parts = explode('$', $storedHash);
            // $parts[0] vazio, $parts[1] versão, $parts[2] hash real
            if (count($parts) >= 3) {
                $version = $parts[1];
                $actualHash = implode('$', array_slice($parts, 2));
            }
        }

        // 2. Buscar o Pepper correspondente à versão do hash
        $pepper = config("services.security.peppers.{$version}");

        if (empty($pepper)) {
            Log::warning("Falha no Login: Pepper da versão '{$version}' não encontrado.");
            return false;
        }

        // 3. Verificar hash (Senha + Pepper)
        return Hash::check($providedPassword . $pepper, $actualHash);
    }

    /**
     * Verifica se o hash precisa ser atualizado (Rehash)
     * Ex: Se mudou o algoritmo ou a versão do Pepper ativa.
     */
    public function needsRehash(string $storedHash): bool
    {
        $activeVersion = config('services.security.active_version', 'v1');
        
        // Se o hash não começa com a versão ativa (ex: $v2$ quando ativo é v2), precisa rehash
        if (!str_starts_with($storedHash, "\${$activeVersion}\$")) {
            return true;
        }

        // Também verifica se o custo do BCRYPT mudou
        $parts = explode('$', $storedHash);
        $realHash = (count($parts) >= 3) ? implode('$', array_slice($parts, 2)) : $storedHash;
        
        // Simula senha+pepper para ver se o Laravel pede rehash do algoritmo
        $pepper = config("services.security.peppers.{$activeVersion}");
        return Hash::needsRehash($realHash, ['rounds' => 10]); // Rounds padrão do Laravel
    }
}