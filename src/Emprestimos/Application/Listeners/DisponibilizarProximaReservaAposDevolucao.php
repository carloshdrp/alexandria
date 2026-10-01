<?php

namespace Emprestimos\Application\Listeners;

use Emprestimos\Domain\Events\EmprestimoFoiDevolvido;
use Emprestimos\Domain\Services\DisponibilizacaoReservaService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Inventario\Domain\Services\AcervoService;

class DisponibilizarProximaReservaAposDevolucao implements ShouldQueue
{
    public int $tries = 5;

    public int $backoff = 60;

    public function __construct(
        private readonly AcervoService $acervo,
        private readonly DisponibilizacaoReservaService $disponibilizacaoReserva,
    ) {}

    public function __invoke(EmprestimoFoiDevolvido $evento): void
    {
        $exemplar = $this->acervo->exemplar($evento->emprestimo->exemplar_id);

        if ($exemplar === null) {
            return;
        }

        $this->disponibilizacaoReserva->disponibilizarProxima($exemplar);
    }
}
