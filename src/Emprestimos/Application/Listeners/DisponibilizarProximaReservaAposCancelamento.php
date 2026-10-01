<?php

namespace Emprestimos\Application\Listeners;

use Emprestimos\Domain\Events\ReservaFoiCancelada;
use Emprestimos\Domain\Services\DisponibilizacaoReservaService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Inventario\Domain\Services\AcervoService;

class DisponibilizarProximaReservaAposCancelamento implements ShouldQueue
{
    public int $tries = 5;

    public int $backoff = 60;

    public function __construct(
        private readonly AcervoService $acervo,
        private readonly DisponibilizacaoReservaService $disponibilizacaoReserva,
    ) {}

    public function __invoke(ReservaFoiCancelada $evento): void
    {
        if ($evento->reserva->exemplar_id === null) {
            return;
        }

        $exemplar = $this->acervo->exemplar($evento->reserva->exemplar_id);

        if ($exemplar === null) {
            return;
        }

        $this->disponibilizacaoReserva->disponibilizarProxima($exemplar);
    }
}
