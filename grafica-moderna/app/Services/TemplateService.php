<?php

namespace App\Services;

class TemplateService
{
    /**
     * Substitui placeholders no formato {{chave}} pelos valores do array.
     */
    public function parse(string $content, array $variables): string
    {
        foreach ($variables as $key => $value) {
            // Garante que o valor seja string para evitar erros
            $strValue = is_array($value) ? json_encode($value) : (string)$value;
            $content = str_replace("{{" . $key . "}}", $strValue, $content);
        }
        return $content;
    }
}