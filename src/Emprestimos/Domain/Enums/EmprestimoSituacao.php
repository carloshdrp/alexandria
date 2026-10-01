<?php

namespace Emprestimos\Domain\Enums;

enum EmprestimoSituacao: int
{
    case Andamento = 1;
    case Devolvido = 2;
    case Atrasado = 3;

    public function rotulo(): string
    {
        return match ($this) {
            self::Andamento => 'Em andamento',
            self::Devolvido => 'Devolvido',
            self::Atrasado => 'Atrasado',
        };
    }

    public function tom(): string
    {
        return match ($this) {
            self::Andamento => 'info',
            self::Devolvido => 'neutral',
            self::Atrasado => 'danger',
        };
    }
}
