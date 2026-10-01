<?php

namespace Emprestimos\Application\Listeners;

use DomainException;
use Emprestimos\Application\Notifications\ReservaReenfileirada;
use Emprestimos\Domain\Events\ReservaFoiReenfileirada;
use Emprestimos\Domain\Models\Reserva;
use Illuminate\Contracts\Queue\ShouldQueue;
use Inventario\Domain\Services\AcervoService;

class NotificarReservaReenfileirada implements ShouldQueue
{
    public int $tries = 5;

    public int $backoff = 60;

    public function __construct(
        private readonly AcervoService $acervo,
    ) {}

    public function __invoke(ReservaFoiReenfileirada $evento): void
    {
        $reserva = $evento->reserva;

        $obra = $this->acervo->obra($reserva->obra_id);

        if ($obra === null) {
            throw new DomainException("Obra {$reserva->obra_id} não existe no acervo.");
        }

        $posicao = Reserva::proximaReserva($reserva->obra_id)
            ->where('enfileirada_em', '<=', $reserva->enfileirada_em)
            ->count();

        $reserva->user->notify(new ReservaReenfileirada($obra->titulo));
    }
}
