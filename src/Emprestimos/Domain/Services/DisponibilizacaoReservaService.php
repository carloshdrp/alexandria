<?php

namespace Emprestimos\Domain\Services;

use Emprestimos\Domain\Events\ReservaFoiDisponibilizada;
use Emprestimos\Domain\Models\Reserva;
use Illuminate\Support\Facades\DB;
use Inventario\Domain\ValueObjects\ExemplarDoAcervo;

class DisponibilizacaoReservaService
{
    public function disponibilizarProxima(ExemplarDoAcervo $exemplar): ?Reserva
    {
        if (! $exemplar->reservado) {
            return null;
        }

        $disponibilizada = null;

        DB::transaction(function () use ($exemplar, &$disponibilizada) {
            $proxima = Reserva::proximaReserva($exemplar->obraId)->lockForUpdate()->first();

            if ($proxima === null) {
                return;
            }

            $proxima->disponibilizar($exemplar->id);
            $proxima->save();

            $disponibilizada = $proxima;
        });

        if ($disponibilizada !== null) {
            event(new ReservaFoiDisponibilizada($disponibilizada));
        }

        return $disponibilizada;
    }
}
