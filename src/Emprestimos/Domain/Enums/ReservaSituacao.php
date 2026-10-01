<?php

namespace Emprestimos\Domain\Enums;

enum ReservaSituacao: int
{
    case Aguardando = 1;
    case Disponivel = 2;
    case Atendida = 3;
    case Expirada = 4;
    case Cancelada = 5;

    public function rotulo(): string
    {
        return match ($this) {
            self::Aguardando => 'Aguardando',
            self::Disponivel => 'Disponível para retirada',
            self::Atendida => 'Atendida',
            self::Expirada => 'Expirada',
            self::Cancelada => 'Cancelada',
        };
    }

    public function tom(): string
    {
        return match ($this) {
            self::Aguardando => 'warning',
            self::Disponivel => 'success',
            self::Atendida => 'neutral',
            self::Expirada => 'neutral',
            self::Cancelada => 'neutral',
        };
    }
}
