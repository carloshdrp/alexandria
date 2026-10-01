<?php

namespace Emprestimos\Application\Listeners;

use DomainException;
use Emprestimos\Application\Notifications\EmprestimoAtrasado;
use Emprestimos\Domain\Events\EmprestimoFoiAtrasado;
use Illuminate\Contracts\Queue\ShouldQueue;
use Inventario\Domain\Services\AcervoService;

class NotificarEmprestimoAtrasado implements ShouldQueue
{
    public int $tries = 5;

    public int $backoff = 60;

    public function __construct(
        private readonly AcervoService $acervo,
    ) {}

    public function __invoke(EmprestimoFoiAtrasado $evento): void
    {
        $emprestimo = $evento->emprestimo;

        $obra = $this->acervo->obraDoExemplar($emprestimo->exemplar_id);

        if ($obra === null) {
            throw new DomainException("Exemplar {$emprestimo->exemplar_id} não existe no acervo.");
        }

        $emprestimo->user->notify(new EmprestimoAtrasado(
            $obra->titulo,
            $emprestimo->prazo->prazoDevolucao(),
            $emprestimo->prazo->diasAtraso(),
        ));
    }
}
