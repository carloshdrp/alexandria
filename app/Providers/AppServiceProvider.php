<?php

namespace App\Providers;

use App\Events\EntityEvent;
use App\Listeners\RegistrarEvento;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use RuntimeException;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        Factory::guessFactoryNamesUsing(function (string $model): string {
            $factory = 'Database\\Factories\\'.class_basename($model).'Factory';

            if (! is_subclass_of($factory, Factory::class)) {
                throw new RuntimeException("Não existe factory para o model {$model}.");
            }

            return $factory;
        });
    }

    public function boot(): void
    {
        Event::listen(EntityEvent::class, RegistrarEvento::class);
    }
}
