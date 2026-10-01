<?php

namespace Emprestimos\Domain\Services;

use Emprestimos\Domain\Events\EmprestimoFoiAvisadoDoVencimento;
use Emprestimos\Domain\Models\Emprestimo;

class AvisoVencimentoService
{
    public function avisarProximos(): int
    {
        $avisados = 0;

        Emprestimo::proximosDoVencimento()->each(function (Emprestimo $emprestimo) use (&$avisados) {
            $emprestimo->marcarAvisoVencimento();
            $emprestimo->save();

            event(new EmprestimoFoiAvisadoDoVencimento($emprestimo));

            $avisados++;
        });

        return $avisados;
    }
}
