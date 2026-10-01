<?php

namespace App\ValueObjects;

use App\Casts\ValueObjectCast;
use Illuminate\Contracts\Database\Eloquent\Castable;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use JsonSerializable;

abstract class AbstractValue implements Castable, JsonSerializable, ValueObject
{
    protected readonly string $valor;

    protected function __construct(string $valor)
    {
        $this->valor = $valor;
    }

    abstract public static function fromNative(mixed $valor): static;

    /**
     * @param  array<int, string>  $arguments
     * @return CastsAttributes<ValueObject, ValueObject>
     */
    public static function castUsing(array $arguments): CastsAttributes
    {
        return new ValueObjectCast(static::class, ...$arguments);
    }

    public function igual(ValueObject $objeto): bool
    {
        return static::class === $objeto::class && $this->getNativeValue() === $objeto->getNativeValue();
    }

    public function getNativeValue(): string
    {
        return $this->valor;
    }

    public function jsonSerialize(): string
    {
        return $this->__toString();
    }

    public function __toString(): string
    {
        return $this->valor;
    }
}
