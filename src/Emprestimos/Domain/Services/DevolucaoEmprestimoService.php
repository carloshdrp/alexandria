<?php

namespace Emprestimos\Domain\Services;

use DomainException;
use Emprestimos\Domain\Models\Emprestimo;
use Emprestimos\Domain\Models\Multa;
use Emprestimos\Domain\Models\Reserva;
use Emprestimos\Domain\ValueObjects\ValorMulta;
use Illuminate\Support\Facades\DB;
use Inventario\Domain\Services\AcervoService;

class DevolucaoEmprestimoService
{
    public function __construct(
        private readonly AcervoService $acervo,
    ) {}

    public function devolver(Emprestimo $emprestimo): void
    {
        $exemplar = $this->acervo->exemplar($emprestimo->exemplar_id);

        if ($exemplar === null) {
            throw new DomainException('Exemplar do empréstimo não existe no acervo.');
        }

        $multa = null;

        DB::transaction(function () use ($emprestimo, $exemplar, &$multa) {
            $emprestimo->devolver();

            if ($emprestimo->prazo->estaAtrasado($emprestimo->devolvido_em)) {
                $dias = $emprestimo->prazo->diasAtraso($emprestimo->devolvido_em);
                $valor = ValorMulta::calcular($dias);

                $multa = Multa::create([
                    'emprestimo_id' => $emprestimo->id,
                    'user_id' => $emprestimo->user_id,
                    'valor' => $valor->total(),
                    'dias_atraso' => $dias,
                ]);
            }

            $emprestimo->save();

            if (Reserva::proximaReserva($exemplar->obraId)->exists()) {
                $this->acervo->marcarReservado($exemplar->id);
            } else {
                $this->acervo->marcarDevolvido($exemplar->id);
            }
        });
    }
}
