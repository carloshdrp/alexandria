<?php

namespace Emprestimos\Application\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class EmprestimosServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        Route::middleware('web')
            ->prefix('emprestimos')
            ->group(__DIR__.'/../Routes/web.php');
    }
}
