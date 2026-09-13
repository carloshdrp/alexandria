<?php

namespace Inventario\Domain\Enums;

enum ExemplarEstadoConservacao: int
{
    case Novo = 1;
    case Bom = 2;
    case Regular = 3;
    case Ruim = 4;
}
