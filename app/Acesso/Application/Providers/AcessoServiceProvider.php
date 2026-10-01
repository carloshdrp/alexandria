<?php

namespace App\Acesso\Application\Providers;

use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class AcessoServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../../Interface/Views', 'acesso');

        Livewire::addNamespace(
            namespace: 'acesso',
            classNamespace: 'App\\Acesso\\Application\\Livewire',
            classPath: __DIR__.'/../Livewire',
            classViewPath: __DIR__.'/../../Interface/Views/livewire',
        );
    }
}
