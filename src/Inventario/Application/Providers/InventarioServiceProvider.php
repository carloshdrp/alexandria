<?php

namespace Inventario\Application\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class InventarioServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        Route::middleware('web')
            ->prefix('inventario')
            ->group(__DIR__.'/../Routes/web.php');
    }
}
