<?php

namespace App\Application\Interfaces;

interface IShippingService
{
    /**
     * @param string $destinationCep
     * @param array $items Lista de itens com peso, dimensões e quantidade
     * @return array Lista de opções de frete calculadas
     */
    public function calculate(string $destinationCep, array $items): array;
}