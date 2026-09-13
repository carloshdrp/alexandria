<?php

namespace Inventario\Domain\ValueObjects;

use App\ValueObjects\AbstractValue;
use InvalidArgumentException;

final class CodigoPatrimonio extends AbstractValue
{
    private const PADRAO = '/^EX-\d{6}$/';

    private readonly string $valor;

    public function __construct(string $valor)
    {
        $normalizado = strtoupper(trim($valor));

        if (! preg_match(self::PADRAO, $normalizado)) {
            throw new InvalidArgumentException('Código de patrimônio inválido.');
        }

        $this->valor = $normalizado;
    }

    public static function fromNative()
    {
        return new self($valor);
    }
}
