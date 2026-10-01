<?php

namespace Emprestimos\Domain\Services;

use Emprestimos\Domain\Models\Reserva;
use Inventario\Domain\Services\AcervoService;

class ReconciliacaoFilaReservaService
{
    public function __construct(
        private readonly AcervoService $acervo,
        private readonly DisponibilizacaoReservaService $disponibilizacaoReserva,
        private readonly LiberacaoExemplarReservadoService $liberacaoExemplar,
    ) {}

    public function reconciliar(): int
    {
        $retomadas = 0;

        foreach (Reserva::obrasComFilaParada()->pluck('obra_id') as $obraId) {
            $exemplar = $this->acervo->exemplarReservado((int) $obraId);

            if ($exemplar === null) {
                continue;
            }

            if ($this->disponibilizacaoReserva->disponibilizarProxima($exemplar) !== null) {
                $retomadas++;
            }
        }

        return $retomadas;
    }

    public function liberarExemplaresOrfaos(): int
    {
        $liberados = 0;

        foreach ($this->acervo->obrasComExemplarReservado() as $obraId) {
            $liberados += $this->liberacaoExemplar->liberarSemReservaAtiva((int) $obraId);
        }

        return $liberados;
    }
}
