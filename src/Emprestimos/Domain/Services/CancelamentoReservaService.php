<?php

namespace Emprestimos\Domain\Services;

use Emprestimos\Domain\Events\ReservaFoiCancelada;
use Emprestimos\Domain\Models\Reserva;
use Illuminate\Support\Facades\DB;

class CancelamentoReservaService
{
    public function __construct(
        private readonly LiberacaoExemplarReservadoService $liberacaoExemplar,
    ) {}

    public function cancelar(Reserva $reserva): void
    {
        DB::transaction(function () use ($reserva) {
            $reserva->cancelar();
            $reserva->save();

            $this->liberacaoExemplar->liberarSemReservaAtiva($reserva->obra_id);
        });

        event(new ReservaFoiCancelada($reserva));
    }
}
