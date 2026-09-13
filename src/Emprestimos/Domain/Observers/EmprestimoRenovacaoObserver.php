<?php

namespace Emprestimos\Domain\Observers;

use DomainException;
use Emprestimos\Domain\Models\EmprestimoRenovacao;

class EmprestimoRenovacaoObserver
{
    public function updating(EmprestimoRenovacao $renovacao): void
    {
        throw new DomainException('O registro de renovação não pode ser atualizado.');
    }
}
