<?php

namespace Inventario\Domain\Enums;

enum ExemplarSituacao: int
{
    case NoAcervo = 1;
    case Emprestado = 2;
    case Baixado = 3;
    case Reservado = 4;
}
