<?php

namespace Inventario\Infrastructure\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use InvalidArgumentException;
use Inventario\Domain\ValueObjects\CodigoPatrimonio;

class CodigoPatrimonioValido implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        try {
            CodigoPatrimonio::fromNative($value);
        } catch (InvalidArgumentException $excecao) {
            $fail($excecao->getMessage());
        }
    }
}
