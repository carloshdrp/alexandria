<?php

namespace App\Events;

interface EntityEvent
{
    public function entityKey(): int|string;
}
