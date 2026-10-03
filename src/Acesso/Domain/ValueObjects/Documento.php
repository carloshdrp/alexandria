<?php

namespace Acesso\Domain\ValueObjects;

use App\ValueObjects\AbstractValue;
use InvalidArgumentException;

final class Documento extends AbstractValue
{
    private function __construct(string $valor)
    {
        $numeros = preg_replace('/\D/', '', $valor) ?? '';

        if (! self::valido($numeros)) {
            throw new InvalidArgumentException('CPF inválido.');
        }

        parent::__construct($numeros);
    }

    private static function valido(string $cpf): bool
    {
        if (strlen($cpf) !== 11) {
            return false;
        }

        return true;
    }

    public static function fromNative(mixed $valor): static
    {
        return new self((string) $valor);
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
}
