<?php

namespace Emprestimos\Application\Listeners;

use DomainException;
use Emprestimos\Application\Notifications\ReservaDisponivel;
use Emprestimos\Domain\Events\ReservaFoiDisponibilizada;
use Illuminate\Contracts\Queue\ShouldQueue;
use Inventario\Domain\Services\AcervoService;

class NotificarReservaDisponivel implements ShouldQueue
{
    public int $tries = 5;

    public int $backoff = 60;

    public function __construct(
        private readonly AcervoService $acervo,
    ) {}

    public function __invoke(ReservaFoiDisponibilizada $evento): void
    {
        $reserva = $evento->reserva;

        $obra = $this->acervo->obra($reserva->obra_id);

        if ($obra === null) {
            throw new DomainException("Obra {$reserva->obra_id} não existe no acervo.");
        }

        if ($reserva->janela === null) {
            throw new DomainException("Reserva {$reserva->id} foi disponibilizada sem janela.");
        }

        $reserva->user->notify(new ReservaDisponivel($obra->titulo, $reserva->janela->expiraEm()));
    }
}
