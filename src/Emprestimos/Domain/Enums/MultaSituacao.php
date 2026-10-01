<?php

namespace Emprestimos\Domain\Enums;

enum MultaSituacao: int
{
    case Pendente = 1;
    case Paga = 2;

    public function rotulo(): string
    {
        return match ($this) {
            self::Pendente => 'Pendente',
            self::Paga => 'Paga',
        };
    }

    public function tom(): string
    {
        return match ($this) {
            self::Pendente => 'danger',
            self::Paga => 'success',
        };
    }
}
