<?php

namespace Inventario\Domain\ValueObjects;

use App\ValueObjects\AbstractValue;
use InvalidArgumentException;

final class CodigoPatrimonio extends AbstractValue
{
    private const PADRAO = '/^EX-\d{6}$/';

    private function __construct(string $valor)
    {
        $normalizado = strtoupper(trim($valor));

        if (! preg_match(self::PADRAO, $normalizado)) {
            throw new InvalidArgumentException('Código de patrimônio inválido.');
        }

        parent::__construct($normalizado);
    }

    public static function fromNative(mixed $valor): static
    {
        return new self((string) $valor);
    }
}
