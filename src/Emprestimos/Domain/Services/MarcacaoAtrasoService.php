<?php

namespace Emprestimos\Domain\Services;

use Emprestimos\Domain\Events\EmprestimoFoiAtrasado;
use Emprestimos\Domain\Models\Emprestimo;

class MarcacaoAtrasoService
{
    public function marcarVencidos(): int
    {
        $marcados = 0;

        Emprestimo::vencidosEmAndamento()->each(function (Emprestimo $emprestimo) use (&$marcados) {
            $emprestimo->marcarAtrasado();
            $emprestimo->save();

            event(new EmprestimoFoiAtrasado($emprestimo));

            $marcados++;
        });

        return $marcados;
    }
}
