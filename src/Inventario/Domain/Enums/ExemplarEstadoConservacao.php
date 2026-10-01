<?php

namespace Inventario\Domain\Enums;

enum ExemplarEstadoConservacao: int
{
    case Novo = 1;
    case Bom = 2;
    case Regular = 3;
    case Ruim = 4;

    public function rotulo(): string
    {
        return match ($this) {
            self::Novo => 'Novo',
            self::Bom => 'Bom',
            self::Regular => 'Regular',
            self::Ruim => 'Ruim',
        };
    }
}
