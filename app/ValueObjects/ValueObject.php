<?php

namespace App\ValueObjects;

interface ValueObject
{
    public static function fromNative(mixed $valor): static;

    public function getNativeValue(): mixed;

    public function igual(ValueObject $objeto): bool;

    public function __toString(): string;
}
