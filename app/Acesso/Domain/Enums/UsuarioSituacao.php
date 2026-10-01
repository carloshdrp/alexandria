<?php

namespace App\Acesso\Domain\Enums;

enum UsuarioSituacao: int
{
    case Ativo = 1;
    case Bloqueado = 2;

    public function rotulo(): string
    {
        return match ($this) {
            self::Ativo => 'Ativo',
            self::Bloqueado => 'Bloqueado',
        };
    }

    public function tom(): string
    {
        return match ($this) {
            self::Ativo => 'success',
            self::Bloqueado => 'danger',
        };
    }
}
