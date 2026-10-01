<?php

namespace Emprestimos\Application\Providers;

use Emprestimos\Application\Listeners\DisponibilizarProximaReservaAposCancelamento;
use Emprestimos\Application\Listeners\DisponibilizarProximaReservaAposDevolucao;
use Emprestimos\Application\Listeners\DisponibilizarProximaReservaAposExpiracao;
use Emprestimos\Domain\Events\EmprestimoFoiDevolvido;
use Emprestimos\Domain\Events\ReservaFoiCancelada;
use Emprestimos\Domain\Events\ReservaFoiExpirada;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class EmprestimosServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        Event::listen(EmprestimoFoiDevolvido::class, DisponibilizarProximaReservaAposDevolucao::class);
        Event::listen(ReservaFoiExpirada::class, DisponibilizarProximaReservaAposExpiracao::class);
        Event::listen(ReservaFoiCancelada::class, DisponibilizarProximaReservaAposCancelamento::class);

        Route::middleware('web')
            ->prefix('emprestimos')
            ->group(__DIR__.'/../../Interface/Routes/web.php');
    }
}
