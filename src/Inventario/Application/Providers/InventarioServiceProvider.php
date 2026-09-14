<?php

namespace Inventario\Application\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Inventario\Domain\Contracts\ArmazenadorCapaObra;
use Inventario\Infrastructure\Storage\ArmazenadorCapaObraEmDisco;

class InventarioServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ArmazenadorCapaObra::class, ArmazenadorCapaObraEmDisco::class);
    }

    public function boot(): void
    {
        Route::middleware('web')
            ->prefix('inventario')
            ->group(__DIR__.'/../../Interface/Routes/web.php');
    }
}
