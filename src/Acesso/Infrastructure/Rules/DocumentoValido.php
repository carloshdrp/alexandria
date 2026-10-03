<?php

namespace Acesso\Infrastructure\Rules;

use Acesso\Domain\ValueObjects\Documento;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use InvalidArgumentException;

class DocumentoValido implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        try {
            Documento::fromNative($value);
        } catch (InvalidArgumentException $excecao) {
            $fail($excecao->getMessage());
        }
    }
}
