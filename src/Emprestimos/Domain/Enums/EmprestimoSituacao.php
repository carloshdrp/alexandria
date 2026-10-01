<?php

namespace Emprestimos\Domain\Enums;

enum EmprestimoSituacao: int
{
    case Andamento = 1;
    case Devolvido = 2;
    case Atrasado = 3;
    case Encerrado = 4;

    public function rotulo(): string
    {
        return match ($this) {
            self::Andamento => 'Em andamento',
            self::Devolvido => 'Devolvido',
            self::Atrasado => 'Atrasado',
            self::Encerrado => 'Encerrado',
        };
    }

    public function tom(): string
    {
        return match ($this) {
            self::Andamento => 'info',
            self::Devolvido => 'neutral',
            self::Atrasado => 'danger',
            self::Encerrado => 'warning',
        };
    }
}
