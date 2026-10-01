<?php

namespace Emprestimos\Application\Listeners;

use DomainException;
use Emprestimos\Application\Notifications\MultaGerada;
use Emprestimos\Domain\Events\MultaFoiGerada;
use Illuminate\Contracts\Queue\ShouldQueue;
use Inventario\Domain\Services\AcervoService;

class NotificarMultaGerada implements ShouldQueue
{
    public int $tries = 5;

    public int $backoff = 60;

    public function __construct(
        private readonly AcervoService $acervo,
    ) {}

    public function __invoke(MultaFoiGerada $evento): void
    {
        $multa = $evento->multa;

        $exemplarId = $multa->emprestimo->exemplar_id;

        $obra = $this->acervo->obraDoExemplar($exemplarId);

        if ($obra === null) {
            throw new DomainException("Exemplar {$exemplarId} não existe no acervo.");
        }

        $multa->user->notify(new MultaGerada($obra->titulo, $multa->valor, $multa->dias_atraso));
    }
}
