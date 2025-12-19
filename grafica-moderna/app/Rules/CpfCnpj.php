<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class CpfCnpj implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $c = preg_replace('/\D/', '', $value);

        if (strlen($c) != 11 && strlen($c) != 14) {
            $fail("O $attribute não é um CPF ou CNPJ válido.");
            return;
        }

        if (strlen($c) == 11) {
            if (!$this->validateCpf($c)) $fail("CPF inválido.");
        } else {
            if (!$this->validateCnpj($c)) $fail("CNPJ inválido.");
        }
    }

    private function validateCpf($cpf)
    {
        if (preg_match('/(\d)\1{10}/', $cpf)) return false;
        for ($t = 9; $t < 11; $t++) {
            for ($d = 0, $c = 0; $c < $t; $c++) $d += $cpf[$c] * (($t + 1) - $c);
            $d = ((10 * $d) % 11) % 10;
            if ($cpf[$c] != $d) return false;
        }
        return true;
    }

    private function validateCnpj($cnpj)
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