<?php

namespace App\Casts;

use App\ValueObjects\ValueObject;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

readonly class ValueObjectCast implements CastsAttributes
{
    public function __construct(
        private string $valueObjectClass,
    ) {
        if (! is_a($valueObjectClass, ValueObject::class, true)) {
            throw new InvalidArgumentException("$valueObjectClass deve implementar ".ValueObject::class.'.');
        }
    }

    public function get(Model $model, string $key, mixed $value, array $attributes): ?ValueObject
    {
        if ($value === null) {
            return null;
        }

        return ($this->valueObjectClass)::fromNative($value);
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        if ($value === null) {
            return null;
        }

        if (! ($value instanceof $this->valueObjectClass)) {
            throw new InvalidArgumentException("O atributo '$key' deve ser uma instância de '{$this->valueObjectClass}'.");
        }

        return $value->getNativeValue();
    }
}
