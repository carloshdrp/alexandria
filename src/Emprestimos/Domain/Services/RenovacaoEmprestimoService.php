<?php

namespace Emprestimos\Domain\Services;

use Emprestimos\Domain\Models\Emprestimo;
use Emprestimos\Domain\Models\Multa;
use Emprestimos\Domain\Models\Reserva;

class RenovacaoEmprestimoService
{
    public function __construct() {}

    public function renovar(Emprestimo $emprestimo): void
    {
        $temMultaPendente = Multa::pendentePorUsuario($emprestimo->user_id)->exists();

        $temReservaPendente = Reserva::reservaPorObra(
            $emprestimo->exemplar->obra_id,
            $emprestimo->user_id
        )->exists();

        $emprestimo->renovar($temReservaPendente, $temMultaPendente);
        $emprestimo->save();
    }
}
