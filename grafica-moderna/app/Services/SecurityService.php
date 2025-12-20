<?php

namespace App\Services;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Exception;

class SecurityService
{
    private $activeVersion;
    private $peppers;

    public function __construct()
    {
        // Carrega configurações do .env (Igual ao C#)
        $this->activeVersion = config('services.security.pepper_active_version', 'v1');
        
        $this->peppers = [
            'v1' => config('services.security.pepper_v1'),
            'v2' => config('services.security.pepper_v2'), // Preparado para rotação futura
        ];

        if (empty($this->peppers[$this->activeVersion])) {
            throw new Exception("CRÍTICO: Pepper para a versão ativa '{$this->activeVersion}' não configurado.");
        }
    }

    /**
     * Cria o Hash da senha usando o Pepper ativo + Prefixo de versão
     * Formato: $v1$HASH_BCRYPT
     */
    public function hashPassword(string $password): string
    {
        $pepper = $this->peppers[$this->activeVersion];
        $hash = Hash::make($password . $pepper);

        return "\${$this->activeVersion}\${$hash}";
    }

    /**
     * Verifica a senha suportando múltiplas versões de Pepper
     */
    public function verifyPassword(string $providedPassword, string $storedHash): bool
    {
        // 1. Detectar versão
        // Formato esperado: $v1$HASH...
        if (str_starts_with($storedHash, '$v')) {
            $parts = explode('$', $storedHash, 3);
            
            // parts[0] = vazio, parts[1] = versão (v1), parts[2] = hash real
            if (count($parts) < 3) return false;
            
            $version = $parts[1];
            $realHash = $parts[2];
        } else {
            // Legado (sem versão explícita, assume v1 ou padrão)
            $version = 'v1';
            $realHash = $storedHash;
        }

        // 2. Buscar o Pepper da versão encontrada
        if (!isset($this->peppers[$version])) {
            Log::warning("Falha no Login: Pepper da versão '{$version}' não encontrado.");
            return false;
        }

        $pepper = $this->peppers[$version];

        // 3. Verificar Hash
        $isValid = Hash::check($providedPassword . $pepper, $realHash);

        // 4. (Opcional) Rehash se a versão mudou (Lógica do C# SuccessRehashNeeded)
        // No Laravel, isso seria feito num middleware ou evento de login, 
        // mas aqui retornamos apenas o booleano.
        
        return $isValid;
    }

    public function needsRehash(string $storedHash): bool
    {
        if (str_starts_with($storedHash, '$v')) {
            $parts = explode('$', $storedHash, 3);
            $version = $parts[1];
            return $version !== $this->activeVersion;
        }
        return true; // Se não tem versão, precisa atualizar para o novo formato
    }
}