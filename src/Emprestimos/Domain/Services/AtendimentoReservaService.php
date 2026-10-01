<?php

namespace Emprestimos\Domain\Services;

use DomainException;
use Emprestimos\Domain\Events\EmprestimoFoiRealizado;
use Emprestimos\Domain\Events\ReservaFoiAtendida;
use Emprestimos\Domain\Models\Emprestimo;
use Emprestimos\Domain\Models\Reserva;
use Illuminate\Support\Facades\DB;
use Inventario\Domain\Services\AcervoService;

class AtendimentoReservaService
{
    public function __construct(
        private readonly AcervoService $acervo,
        private readonly RealizacaoEmprestimoService $realizacaoEmprestimo,
    ) {}

    public function atender(Reserva $reserva): Emprestimo
    {
        if ($reserva->janela === null || $reserva->janela->expirou()) {
            throw new DomainException('A janela de retirada da reserva expirou.');
        }

        if ($reserva->exemplar_id === null) {
            throw new DomainException('A reserva não possui exemplar disponibilizado.');
        }

        $exemplar = $this->acervo->exemplar($reserva->exemplar_id);

        if ($exemplar === null) {
            throw new DomainException('Exemplar da reserva não existe no acervo.');
        }

        $this->realizacaoEmprestimo->garantirElegibilidade($reserva->user);

        $emprestimo = DB::transaction(function () use ($reserva, $exemplar) {
            $reserva->atender();
            $reserva->save();

            $this->acervo->marcarEmprestadoParaReserva($exemplar->id);

            return $this->realizacaoEmprestimo->registrar($reserva->user_id, $exemplar->id);
        });

        event(new ReservaFoiAtendida($reserva));
        event(new EmprestimoFoiRealizado($emprestimo));

        return $emprestimo;
    }
}
