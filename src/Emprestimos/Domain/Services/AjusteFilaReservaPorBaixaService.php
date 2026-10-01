<?php

namespace Emprestimos\Domain\Services;

use Emprestimos\Domain\Events\ReservaFoiCanceladaPorIndisponibilidade;
use Emprestimos\Domain\Events\ReservaFoiReenfileirada;
use Emprestimos\Domain\Models\Reserva;
use Illuminate\Support\Facades\DB;
use Inventario\Domain\ValueObjects\ExemplarDoAcervo;
use Inventario\Domain\ValueObjects\ObraDoAcervo;

class AjusteFilaReservaPorBaixaService
{
    public function ajustarAposBaixa(ExemplarDoAcervo $exemplar, ObraDoAcervo $obra): int
    {
        if ($obra->exemplaresTotal === 0) {
            return $this->cancelarFilaDaObra($obra);
        }

        return $this->reenfileirarPresasAoExemplar($exemplar);
    }

    private function cancelarFilaDaObra(ObraDoAcervo $obra): int
    {
        $canceladas = [];

        DB::transaction(function () use ($obra, &$canceladas) {
            foreach (Reserva::reservaPorObra($obra->id)->lockForUpdate()->get() as $reserva) {
                $reserva->cancelar();
                $reserva->save();

                $canceladas[] = $reserva;
            }
        });

        foreach ($canceladas as $reserva) {
            event(new ReservaFoiCanceladaPorIndisponibilidade($reserva));
        }

        return count($canceladas);
    }

    private function reenfileirarPresasAoExemplar(ExemplarDoAcervo $exemplar): int
    {
        $reenfileiradas = [];

        DB::transaction(function () use ($exemplar, &$reenfileiradas) {
            foreach (Reserva::disponiveisPorExemplar($exemplar->id)->lockForUpdate()->get() as $reserva) {
                $reserva->reenfileirar();
                $reserva->save();

                $reenfileiradas[] = $reserva;
            }
        });

        foreach ($reenfileiradas as $reserva) {
            event(new ReservaFoiReenfileirada($reserva));
        }

        return count($reenfileiradas);
    }
}
