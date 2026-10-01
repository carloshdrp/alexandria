<?php

namespace App\Casts;

use App\ValueObjects\ValueObject;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

/**
 * @implements CastsAttributes<ValueObject, ValueObject>
 */
readonly class ValueObjectCast implements CastsAttributes
{
    /** @var class-string<ValueObject> */
    private string $valueObjectClass;

    /** @var list<string> */
    private array $argumentos;

    public function __construct(string $valueObjectClass, string ...$argumentos)
    {
        if (! is_a($valueObjectClass, ValueObject::class, true)) {
            throw new InvalidArgumentException("$valueObjectClass deve implementar ".ValueObject::class.'.');
        }

        $this->valueObjectClass = $valueObjectClass;
        $this->argumentos = array_values($argumentos);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?ValueObject
    {
        if ($value === null) {
            return null;
        }

        return ($this->valueObjectClass)::fromNative($value, ...$this->argumentos);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
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
