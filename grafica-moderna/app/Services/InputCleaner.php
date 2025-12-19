<?php

namespace App\Services\Security;

class InputCleaner
{
    /**
     * Remove qualquer tag HTML e espaços extras.
     * Ideal para Nomes, Endereços, Telefones, etc.
     */
    public static function clean(mixed $value): mixed
    {
        if (is_string($value)) {
            // strip_tags: remove <script>, <b>, <img>... tudo.
            // trim: remove espaços em branco no início/fim
            return trim(strip_tags($value));
        }
        
        if (is_array($value)) {
            return array_map([self::class, 'clean'], $value);
        }

        return $value;
    }
}