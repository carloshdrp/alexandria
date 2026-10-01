<?php

namespace Emprestimos\Application\Providers;

use Emprestimos\Application\Listeners\AjustarFilaDeReservaAposBaixa;
use Emprestimos\Application\Listeners\DisponibilizarProximaReservaAposCancelamento;
use Emprestimos\Application\Listeners\DisponibilizarProximaReservaAposDevolucao;
use Emprestimos\Application\Listeners\DisponibilizarProximaReservaAposExpiracao;
use Emprestimos\Application\Listeners\EncerrarEmprestimoDoExemplarBaixado;
use Emprestimos\Application\Listeners\NotificarEmprestimoAtrasado;
use Emprestimos\Application\Listeners\NotificarEmprestimoDevolvido;
use Emprestimos\Application\Listeners\NotificarEmprestimoEncerrado;
use Emprestimos\Application\Listeners\NotificarEmprestimoRealizado;
use Emprestimos\Application\Listeners\NotificarEmprestimoRenovado;
use Emprestimos\Application\Listeners\NotificarMultaGerada;
use Emprestimos\Application\Listeners\NotificarMultaPaga;
use Emprestimos\Application\Listeners\NotificarReservaCancelada;
use Emprestimos\Application\Listeners\NotificarReservaCanceladaPorIndisponibilidade;
use Emprestimos\Application\Listeners\NotificarReservaDisponivel;
use Emprestimos\Application\Listeners\NotificarReservaExpirada;
use Emprestimos\Application\Listeners\NotificarReservaReenfileirada;
use Emprestimos\Application\Listeners\NotificarVencimentoProximo;
use Emprestimos\Domain\Events\EmprestimoFoiAtrasado;
use Emprestimos\Domain\Events\EmprestimoFoiAvisadoDoVencimento;
use Emprestimos\Domain\Events\EmprestimoFoiDevolvido;
use Emprestimos\Domain\Events\EmprestimoFoiEncerradoPorBaixa;
use Emprestimos\Domain\Events\EmprestimoFoiRealizado;
use Emprestimos\Domain\Events\EmprestimoFoiRenovado;
use Emprestimos\Domain\Events\MultaFoiGerada;
use Emprestimos\Domain\Events\MultaFoiPaga;
use Emprestimos\Domain\Events\ReservaFoiCancelada;
use Emprestimos\Domain\Events\ReservaFoiCanceladaPorIndisponibilidade;
use Emprestimos\Domain\Events\ReservaFoiDisponibilizada;
use Emprestimos\Domain\Events\ReservaFoiExpirada;
use Emprestimos\Domain\Events\ReservaFoiReenfileirada;
use Emprestimos\Application\Policies\EmprestimoPolicy;
use Emprestimos\Application\Policies\MultaPolicy;
use Emprestimos\Application\Policies\ReservaPolicy;
use Emprestimos\Domain\Models\Emprestimo;
use Emprestimos\Domain\Models\Multa;
use Emprestimos\Domain\Models\Reserva;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Inventario\Domain\Events\Integracao\ExemplarFoiBaixado;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class EmprestimosServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Event::listen(EmprestimoFoiDevolvido::class, DisponibilizarProximaReservaAposDevolucao::class);
        Event::listen(ReservaFoiExpirada::class, DisponibilizarProximaReservaAposExpiracao::class);
        Event::listen(ReservaFoiCancelada::class, DisponibilizarProximaReservaAposCancelamento::class);

        Event::listen(ExemplarFoiBaixado::class, EncerrarEmprestimoDoExemplarBaixado::class);
        Event::listen(ExemplarFoiBaixado::class, AjustarFilaDeReservaAposBaixa::class);

        Event::listen(EmprestimoFoiRealizado::class, NotificarEmprestimoRealizado::class);
        Event::listen(EmprestimoFoiDevolvido::class, NotificarEmprestimoDevolvido::class);
        Event::listen(EmprestimoFoiRenovado::class, NotificarEmprestimoRenovado::class);
        Event::listen(EmprestimoFoiAtrasado::class, NotificarEmprestimoAtrasado::class);
        Event::listen(EmprestimoFoiAvisadoDoVencimento::class, NotificarVencimentoProximo::class);
        Event::listen(MultaFoiGerada::class, NotificarMultaGerada::class);
        Event::listen(MultaFoiPaga::class, NotificarMultaPaga::class);
        Event::listen(ReservaFoiDisponibilizada::class, NotificarReservaDisponivel::class);
        Event::listen(ReservaFoiExpirada::class, NotificarReservaExpirada::class);
        Event::listen(ReservaFoiCancelada::class, NotificarReservaCancelada::class);
        Event::listen(EmprestimoFoiEncerradoPorBaixa::class, NotificarEmprestimoEncerrado::class);
        Event::listen(ReservaFoiCanceladaPorIndisponibilidade::class, NotificarReservaCanceladaPorIndisponibilidade::class);
        Event::listen(ReservaFoiReenfileirada::class, NotificarReservaReenfileirada::class);

        Gate::policy(Emprestimo::class, EmprestimoPolicy::class);
        Gate::policy(Reserva::class, ReservaPolicy::class);
        Gate::policy(Multa::class, MultaPolicy::class);

        $this->loadViewsFrom(__DIR__.'/../../Interface/Views', 'emprestimos');

        Livewire::addNamespace(
            namespace: 'emprestimos',
            classNamespace: 'Emprestimos\\Application\\Livewire',
            classPath: __DIR__.'/../Livewire',
            classViewPath: __DIR__.'/../../Interface/Views/livewire',
        );

        Route::middleware(['web', 'auth', 'verified'])
            ->prefix('emprestimos')
            ->name('emprestimos.')
            ->group(__DIR__.'/../../Interface/Routes/web.php');
    }
}
