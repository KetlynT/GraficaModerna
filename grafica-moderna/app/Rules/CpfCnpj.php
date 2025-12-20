<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class CpfCnpj implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_string($value)) {
            $fail('O documento deve ser uma string.');
            return;
        }

        // Remove caracteres não numéricos
        $c = preg_replace('/\D/', '', $value);

        if (strlen($c) === 11) {
            if (!$this->isCpfValid($c)) {
                $fail('O CPF informado é inválido.');
            }
            return;
        }

        if (strlen($c) === 14) {
            if (!$this->isCnpjValid($c)) {
                $fail('O CNPJ informado é inválido.');
            }
            return;
        }

        $fail('O documento deve ter 11 (CPF) ou 14 (CNPJ) dígitos.');
    }

    // Lógica portada fielmente do DocumentValidator.cs (IsCpfValid)
    private function isCpfValid(string $cpf): bool
    {
        if (preg_match("/^{$cpf[0]}{11}$/", $cpf)) return false;

        $multiplier1 = [10, 9, 8, 7, 6, 5, 4, 3, 2];
        $multiplier2 = [11, 10, 9, 8, 7, 6, 5, 4, 3, 2];

        $tempCpf = substr($cpf, 0, 9);
        $sum = 0;

        for ($i = 0; $i < 9; $i++) {
            $sum += intval($tempCpf[$i]) * $multiplier1[$i];
        }

        $remainder = $sum % 11;
        $remainder = ($remainder < 2) ? 0 : 11 - $remainder;

        $digit = (string)$remainder;
        $tempCpf .= $digit;
        $sum = 0;

        for ($i = 0; $i < 10; $i++) {
            $sum += intval($tempCpf[$i]) * $multiplier2[$i];
        }

        $remainder = $sum % 11;
        $remainder = ($remainder < 2) ? 0 : 11 - $remainder;

        $digit .= (string)$remainder;

        return str_ends_with($cpf, $digit);
    }

    // Lógica portada fielmente do DocumentValidator.cs (IsCnpjValid)
    private function isCnpjValid(string $cnpj): bool
    {
        $multiplier1 = [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
        $multiplier2 = [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];

        $tempCnpj = substr($cnpj, 0, 12);
        $sum = 0;

        for ($i = 0; $i < 12; $i++) {
            $sum += intval($tempCnpj[$i]) * $multiplier1[$i];
        }

        $remainder = $sum % 11;
        $remainder = ($remainder < 2) ? 0 : 11 - $remainder;

        $digit = (string)$remainder;
        $tempCnpj .= $digit;
        $sum = 0;

        for ($i = 0; $i < 13; $i++) {
            $sum += intval($tempCnpj[$i]) * $multiplier2[$i];
        }

        $remainder = $sum % 11;
        $remainder = ($remainder < 2) ? 0 : 11 - $remainder;

        $digit .= (string)$remainder;

        return str_ends_with($cnpj, $digit);
    }
}