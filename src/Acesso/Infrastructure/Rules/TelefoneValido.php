<?php

namespace Acesso\Infrastructure\Rules;

use Acesso\Domain\ValueObjects\Telefone;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use InvalidArgumentException;

class TelefoneValido implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        try {
            Telefone::fromNative($value);
        } catch (InvalidArgumentException $excecao) {
            $fail($excecao->getMessage());
        }
    }
}
