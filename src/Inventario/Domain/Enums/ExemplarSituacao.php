<?php

namespace Inventario\Domain\Enums;

enum ExemplarSituacao: int
{
    case NoAcervo = 1;
    case Emprestado = 2;
    case Baixado = 3;
    case Reservado = 4;

    public function rotulo(): string
    {
        return match ($this) {
            self::NoAcervo => 'No acervo',
            self::Emprestado => 'Emprestado',
            self::Baixado => 'Baixado',
            self::Reservado => 'Reservado',
        };
    }

    public function tom(): string
    {
        return match ($this) {
            self::NoAcervo => 'success',
            self::Emprestado => 'info',
            self::Baixado => 'neutral',
            self::Reservado => 'warning',
        };
    }
}
