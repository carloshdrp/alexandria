<?php

namespace Inventario\Domain\Enums;

enum ExemplarMotivoBaixa: int
{
    case Perdido = 1;
    case Danificado = 2;
    case Vendido = 3;
}
