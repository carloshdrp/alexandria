<?php

namespace Emprestimos\Application\Listeners;

use DomainException;
use Emprestimos\Application\Notifications\EmprestimoRealizado;
use Emprestimos\Domain\Events\EmprestimoFoiRealizado;
use Illuminate\Contracts\Queue\ShouldQueue;
use Inventario\Domain\Services\AcervoService;

class NotificarEmprestimoRealizado implements ShouldQueue
{
    public int $tries = 5;

    public int $backoff = 60;

    public function __construct(
        private readonly AcervoService $acervo,
    ) {}

    public function __invoke(EmprestimoFoiRealizado $evento): void
    {
        $emprestimo = $evento->emprestimo;

        $exemplar = $this->acervo->exemplar($emprestimo->exemplar_id);

        if ($exemplar === null) {
            throw new DomainException("Exemplar {$emprestimo->exemplar_id} não existe no acervo.");
        }

        $obra = $this->acervo->obra($exemplar->obraId);

        if ($obra === null) {
            throw new DomainException("Obra {$exemplar->obraId} não existe no acervo.");
        }

        $emprestimo->user->notify(new EmprestimoRealizado(
            $obra->titulo,
            $exemplar->codigoPatrimonio,
            $emprestimo->prazo->prazoDevolucao(),
        ));
    }
}
