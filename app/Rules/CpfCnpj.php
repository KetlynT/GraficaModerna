<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class CpfCnpj implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!$this->isValid($value)) {
            $fail("O documento informado (CPF ou CNPJ) é inválido.");
        }
    }

    private function isValid($value): bool
    {
        $c = preg_replace('/\D/', '', $value);
        if (strlen($c) == 11) return $this->validateCpf($c);
        if (strlen($c) == 14) return $this->validateCnpj($c);
        return false;
    }

    private function validateCpf($cpf): bool
    {
        if (preg_match('/(\d)\1{10}/', $cpf)) return false;
        for ($t = 9; $t < 11; $t++) {
            for ($d = 0, $c = 0; $c < $t; $c++) $d += $cpf[$c] * (($t + 1) - $c);
            $d = ((10 * $d) % 11) % 10;
            if ($cpf[$c] != $d) return false;
        }
        return true;
    }

    private function validateCnpj($cnpj): bool
    {
        if (preg_match('/(\d)\1{13}/', $cnpj)) return false;
        $b = [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
        for ($i = 0, $n = 0; $i < 12; $n += $cnpj[$i] * $b[++$i]);
        if ($cnpj[12] != ((($n %= 11) < 2) ? 0 : 11 - $n)) return false;
        for ($i = 0, $n = 0; $i <= 12; $n += $cnpj[$i] * $b[$i++]);
        if ($cnpj[13] != ((($n %= 11) < 2) ? 0 : 11 - $n)) return false;
        return true;
    }
}