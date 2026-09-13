<?php

namespace App\ValueObjects;

use InvalidArgumentException;

final class Telefone extends AbstractValue
{
    private readonly string $valor;

    public function __construct(string $valor)
    {
        $normalizado = preg_replace('/\D/', '', $valor) ?? '';

        if (! self::valido($normalizado)) {
            throw new InvalidArgumentException('Telefone inválido.');
        }

        $this->valor = $normalizado;
    }

    public static function fromNative($valor): self
    {
        return new self($valor);
    }

    private static function valido(string $numero): bool
    {
        if (! preg_match('/^[1-9]{2}9?\d{8}$/', $numero)) {
            return false;
        }

        return true;
    }
}
