<?php

namespace Emprestimos\Application\Listeners;

use DomainException;
use Emprestimos\Application\Notifications\MultaPaga;
use Emprestimos\Domain\Events\MultaFoiPaga;
use Illuminate\Contracts\Queue\ShouldQueue;
use Inventario\Domain\Services\AcervoService;

class NotificarMultaPaga implements ShouldQueue
{
    public int $tries = 5;

    public int $backoff = 60;

    public function __construct(
        private readonly AcervoService $acervo,
    ) {}

    public function __invoke(MultaFoiPaga $evento): void
    {
        $multa = $evento->multa;

        $exemplarId = $multa->emprestimo->exemplar_id;

        $obra = $this->acervo->obraDoExemplar($exemplarId);

        if ($obra === null) {
            throw new DomainException("Exemplar {$exemplarId} não existe no acervo.");
        }

        $multa->user->notify(new MultaPaga($obra->titulo, $multa->valor));
    }
}
