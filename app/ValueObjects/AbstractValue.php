<?php

namespace App\ValueObjects;

use JsonSerializable;

abstract class AbstractValue implements JsonSerializable, ValueObject
{
    public static function fromNative($valor)
    {
        return new static($valor);
    }

    public function getNativeValue()
    {
        return $this->valor;
    }

    public function igual(ValueObject $objeto): bool
    {
        return static::class === $objeto::class && $this->getNativeValue() === $objeto->getNativeValue();
    }

    public function __toString(): string
    {
        return (string) $this->valor;
    }

    public function jsonSerialize(): string
    {
        return $this->__toString();
    }
}
