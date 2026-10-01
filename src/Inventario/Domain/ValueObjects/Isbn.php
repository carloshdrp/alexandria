<?php

namespace Inventario\Domain\ValueObjects;

use App\ValueObjects\AbstractValue;
use InvalidArgumentException;

final class Isbn extends AbstractValue
{
    private function __construct(string $valor)
    {
        $normalizado = strtoupper(preg_replace('/[^0-9X]/', '', $valor) ?? '');

        if (! self::valido($normalizado)) {
            throw new InvalidArgumentException('ISBN inválido');
        }

        parent::__construct($normalizado);
    }

    private static function valido(string $isbn): bool
    {
        return match (strlen($isbn)) {
            10 => self::validoISBN10($isbn),
            13 => self::validoISBN13($isbn),
            default => false,
        };
    }

    private static function validoISBN10(string $isbn): bool
    {
        $soma = 0;
        for ($i = 0; $i < 10; $i++) {
            $digito = $isbn[$i] === 'X' ? 10 : (int) $isbn[$i];
            $soma += $digito * (10 - $i);
        }

        return $soma % 11 === 0;
    }

    private static function validoISBN13(string $isbn): bool
    {
        if (! ctype_digit($isbn)) {
            return false;
        }

        $soma = 0;
        for ($i = 0; $i < 13; $i++) {
            $soma += (int) $isbn[$i] * ($i % 2 === 0 ? 1 : 3);
        }

        return $soma % 10 === 0;
    }

    public static function fromNative(mixed $valor): static
    {
        return new self((string) $valor);
    }
}
