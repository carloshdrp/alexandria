<?php

namespace Emprestimos\Application\Listeners;

use Emprestimos\Domain\Services\AjusteFilaReservaPorBaixaService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Inventario\Domain\Events\Integracao\ExemplarFoiBaixado;
use Inventario\Domain\Services\AcervoService;

class AjustarFilaDeReservaAposBaixa implements ShouldQueue
{
    public int $tries = 5;

    public int $backoff = 60;

    public bool $afterCommit = true;

    public function __construct(
        private readonly AcervoService $acervo,
        private readonly AjusteFilaReservaPorBaixaService $ajusteFila,
    ) {}

    public function __invoke(ExemplarFoiBaixado $evento): void
    {
        $exemplar = $this->acervo->exemplar($evento->exemplarId);
        $obra = $this->acervo->obra($evento->obraId);

        if ($exemplar === null || $obra === null) {
            return;
        }

        $this->ajusteFila->ajustarAposBaixa($exemplar, $obra);
    }
}
