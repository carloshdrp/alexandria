<?php

namespace Acesso\Domain\Enums;

enum UsuarioPapel: int
{
    case Cliente = 1;
    case Bibliotecario = 2;

    public function rotulo(): string
    {
        return match ($this) {
            self::Cliente => 'Cliente',
            self::Bibliotecario => 'Bibliotecário',
        };
    }
}
