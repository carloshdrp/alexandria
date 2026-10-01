<?php

namespace App\Providers;

use App\Events\EntityEvent;
use App\Listeners\RegistrarEvento;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Event::listen(EntityEvent::class, RegistrarEvento::class);
    }
}
