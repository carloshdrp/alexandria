<?php

namespace App\Listeners;

use App\Events\EntityEvent;
use Illuminate\Support\Facades\Log;

class RegistrarEvento
{
    public function __invoke(EntityEvent $evento): void
    {
        Log::info($evento::class, ['entidade' => $evento->entityKey()]);
    }
}
