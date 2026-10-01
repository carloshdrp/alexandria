<?php

namespace Emprestimos\Application\Listeners;

use DomainException;
use Emprestimos\Application\Notifications\ReservaExpirada;
use Emprestimos\Domain\Events\ReservaFoiExpirada;
use Illuminate\Contracts\Queue\ShouldQueue;
use Inventario\Domain\Services\AcervoService;

class NotificarReservaExpirada implements ShouldQueue
{
    public int $tries = 5;

    public int $backoff = 60;

    public function __construct(
        private readonly AcervoService $acervo,
    ) {}

    public function __invoke(ReservaFoiExpirada $evento): void
    {
        $reserva = $evento->reserva;

        $obra = $this->acervo->obra($reserva->obra_id);

        if ($obra === null) {
            throw new DomainException("Obra {$reserva->obra_id} não existe no acervo.");
        }

        $reserva->user->notify(new ReservaExpirada($obra->titulo));
    }
}
