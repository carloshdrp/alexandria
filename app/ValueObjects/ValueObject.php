<?php

namespace App\ValueObjects;

interface ValueObject
{
    public static function fromNative($valor);

    public function getNativeValue();

    public function igual(ValueObject $objeto);

    public function __toString();
}
