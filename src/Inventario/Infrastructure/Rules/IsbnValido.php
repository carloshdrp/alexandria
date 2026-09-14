<?php

namespace Inventario\Infrastructure\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use InvalidArgumentException;
use Inventario\Domain\ValueObjects\Isbn;

class IsbnValido implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        try {
            Isbn::fromNative($value);
        } catch (InvalidArgumentException $excecao) {
            $fail($excecao->getMessage());
        }
    }
}
