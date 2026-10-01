<?php

namespace Emprestimos\Domain\Services;

use Emprestimos\Domain\Events\ReservaFoiExpirada;
use Emprestimos\Domain\Models\Reserva;
use Illuminate\Support\Facades\DB;

class ExpiracaoReservaService
{
    public function __construct(
        private readonly LiberacaoExemplarReservadoService $liberacaoExemplar,
    ) {}

    public function expirarVencidas(): int
    {
        $expiradas = 0;

        Reserva::disponiveisVencidas()->each(function (Reserva $reserva) use (&$expiradas) {
            DB::transaction(function () use ($reserva) {
                $reserva->expirar();
                $reserva->save();

                $this->liberacaoExemplar->liberarSemReservaAtiva($reserva->obra_id);
            });

            event(new ReservaFoiExpirada($reserva));

            $expiradas++;
        });

        return $expiradas;
    }
}
