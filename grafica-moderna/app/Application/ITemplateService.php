<?php

namespace App\Application\Interfaces;

interface ITemplateService
{
    /**
     * Renderiza o template e retorna Assunto e Corpo
     * @return array{subject: string, body: string}
     */
    public function renderEmail(string $templateKey, mixed $model): array;
}