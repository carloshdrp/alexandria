<?php

namespace Emprestimos\Application\Listeners;

use DomainException;
use Emprestimos\Application\Notifications\VencimentoProximo;
use Emprestimos\Domain\Events\EmprestimoFoiAvisadoDoVencimento;
use Emprestimos\Domain\Models\Emprestimo;
use Illuminate\Contracts\Queue\ShouldQueue;
use Inventario\Domain\Services\AcervoService;

class NotificarVencimentoProximo implements ShouldQueue
{
    public int $tries = 5;

    public int $backoff = 60;

    public function __construct(
        private readonly AcervoService $acervo,
    ) {}

    public function __invoke(EmprestimoFoiAvisadoDoVencimento $evento): void
    {
        $emprestimo = $evento->emprestimo;

        $obra = $this->acervo->obraDoExemplar($emprestimo->exemplar_id);

        if ($obra === null) {
            throw new DomainException("Exemplar {$emprestimo->exemplar_id} não existe no acervo.");
        }

        $emprestimo->user->notify(new VencimentoProximo(
            $obra->titulo,
            $emprestimo->prazo->prazoDevolucao(),
            $emprestimo->qtd_renovacoes < Emprestimo::MAX_RENOVACOES,
        ));
    }
}
