<?php

namespace App\ValueObjects;

use InvalidArgumentException;

final class Documento extends AbstractValue
{
    private readonly string $valor;

    private function __construct(string $valor)
    {
        $numeros = preg_replace('/\D/', '', $valor) ?? '';

        if (! self::valido($numeros)) {
            throw new InvalidArgumentException('CPF inválido.');
        }

        $this->valor = $numeros;
    }

    public static function fromNative($valor): self
    {
        return new self($valor);
    }

    public function formatado(): string
    {
        return sprintf('%s.%s.%s-%s',
            substr($this->valor, 0, 3),
            substr($this->valor, 3, 3),
            substr($this->valor, 6, 3),
            substr($this->valor, 9, 2)
        );
    }

    private static function valido(string $cpf): bool
    {
        if (strlen($cpf) !== 11) {
            return false;
        }

        return true;
    }
}
