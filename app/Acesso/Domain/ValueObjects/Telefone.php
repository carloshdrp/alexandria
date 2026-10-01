<?php

namespace App\Acesso\Domain\ValueObjects;

use App\ValueObjects\AbstractValue;
use InvalidArgumentException;

final class Telefone extends AbstractValue
{
    private function __construct(string $valor)
    {
        $normalizado = preg_replace('/\D/', '', $valor) ?? '';

        if (! self::valido($normalizado)) {
            throw new InvalidArgumentException('Telefone inválido.');
        }

        parent::__construct($normalizado);
    }

    private static function valido(string $numero): bool
    {
        if (! preg_match('/^[1-9]{2}9?\d{8}$/', $numero)) {
            return false;
        }

        return true;
    }

    public static function fromNative(mixed $valor): static
    {
        return new self((string) $valor);
    }
}
