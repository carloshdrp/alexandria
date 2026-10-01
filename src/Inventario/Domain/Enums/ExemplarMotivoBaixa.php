<?php

namespace Inventario\Domain\Enums;

enum ExemplarMotivoBaixa: int
{
    case Perdido = 1;
    case Danificado = 2;
    case Vendido = 3;

    public function rotulo(): string
    {
        return match ($this) {
            self::Perdido => 'Perdido',
            self::Danificado => 'Danificado',
            self::Vendido => 'Vendido',
        };
    }
}
