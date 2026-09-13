<?php

namespace Emprestimos\Domain\Services;

use Emprestimos\Domain\Models\Emprestimo;
use Emprestimos\Domain\Models\Multa;
use Emprestimos\Domain\Models\Reserva;
use Emprestimos\Domain\ValueObjects\ValorMulta;
use Illuminate\Support\Facades\DB;

class DevolucaoEmprestimoService
{
    public function __construct() {}

    public function devolver(Emprestimo $emprestimo): void
    {
        DB::transaction(function () use ($emprestimo) {
            $emprestimo->devolver();

            if ($emprestimo->prazo->estaAtrasado($emprestimo->devolvido_em)) {
                $dias = $emprestimo->prazo->diasAtraso($emprestimo->devolvido_em);
                $valor = ValorMulta::calcular($dias);

                Multa::create([
                    'emprestimo_id' => $emprestimo->id,
                    'user_id' => $emprestimo->user_id,
                    'valor' => $valor->total(),
                    'dias_atraso' => $dias,
                ]);
            }

            $emprestimo->save();

            $exemplar = $emprestimo->exemplar;
            $proximaReserva = Reserva::proximaReserva($exemplar->obra_id)->first();

            if ($proximaReserva) {
                $proximaReserva->disponibilizar($exemplar->id);
                $proximaReserva->save();
                // TODO: Disparar evento de reserva liberada quando notificação/schedule entrar no escopo
            } else {
                $exemplar->devolver();
                $exemplar->save();
            }
        });
    }
}
