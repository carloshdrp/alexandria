<?php

namespace Emprestimos\Application\Listeners;

use Emprestimos\Domain\Events\EmprestimoFoiEncerradoPorBaixa;
use Emprestimos\Domain\Models\Emprestimo;
use Illuminate\Contracts\Queue\ShouldQueue;
use Inventario\Domain\Events\Integracao\ExemplarFoiBaixado;

class EncerrarEmprestimoDoExemplarBaixado implements ShouldQueue
{
    public int $tries = 5;

    public int $backoff = 60;

    public bool $afterCommit = true;

    public function __invoke(ExemplarFoiBaixado $evento): void
    {
        $emprestimo = Emprestimo::ativoPorExemplar($evento->exemplarId)->first();

        if ($emprestimo === null) {
            return;
        }

        $emprestimo->encerrarPorBaixaDoExemplar();
        $emprestimo->save();

        event(new EmprestimoFoiEncerradoPorBaixa($emprestimo));
    }
}
