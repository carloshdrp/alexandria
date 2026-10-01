<?php

namespace Emprestimos\Domain\Services;

use Emprestimos\Domain\Models\Reserva;
use Inventario\Domain\Services\AcervoService;

class LiberacaoExemplarReservadoService
{
    public function __construct(
        private readonly AcervoService $acervo,
    ) {}

    public function liberarSemReservaAtiva(int $obraId): int
    {
        if (Reserva::proximaReserva($obraId)->exists()) {
            return 0;
        }

        $comReserva = Reserva::disponiveisPorObra($obraId)->pluck('exemplar_id')->all();
        $liberados = 0;

        foreach ($this->acervo->exemplaresReservados($obraId) as $exemplar) {
            if (in_array($exemplar->id, $comReserva, true)) {
                continue;
            }

            $this->acervo->liberarReserva($exemplar->id);
            $liberados++;
        }

        return $liberados;
    }
}
