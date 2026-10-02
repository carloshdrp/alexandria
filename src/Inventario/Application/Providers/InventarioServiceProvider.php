<?php

namespace Inventario\Application\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Inventario\Application\Listeners\GerarMiniaturaDaCapa;
use Inventario\Domain\Events\ObraFoiAtualizada;
use Inventario\Domain\Events\ObraFoiCadastrada;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class InventarioServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Event::listen(ObraFoiCadastrada::class, GerarMiniaturaDaCapa::class);
        Event::listen(ObraFoiAtualizada::class, GerarMiniaturaDaCapa::class);

        $this->loadViewsFrom(__DIR__.'/../../Interface/Views', 'inventario');

        Livewire::addNamespace(
            namespace: 'inventario',
            classNamespace: 'Inventario\\Application\\Livewire',
            classPath: __DIR__.'/../Livewire',
            classViewPath: __DIR__.'/../../Interface/Views/livewire',
        );

        Route::middleware(['web', 'auth', 'verified', 'can:bibliotecario'])
            ->prefix('inventario')
            ->name('inventario.')
            ->group(__DIR__.'/../../Interface/Routes/web.php');
    }
}
