<?php

namespace Emprestimos\Domain\Observers;

use DomainException;
use Emprestimos\Domain\Enums\EmprestimoSituacao;
use Emprestimos\Domain\Models\Emprestimo;

class EmprestimoObserver
{
    private const array TRANSICOES_VALIDAS = [
        EmprestimoSituacao::Andamento->value => [EmprestimoSituacao::Devolvido, EmprestimoSituacao::Atrasado],
        EmprestimoSituacao::Atrasado->value => [EmprestimoSituacao::Devolvido],
        EmprestimoSituacao::Devolvido->value => [],
    ];

    public function saving(Emprestimo $emprestimo): void
    {
        if (! $emprestimo->exists || ! $emprestimo->isDirty('situacao')) {
            return;
        }

        $de = EmprestimoSituacao::from($emprestimo->getRawOriginal('situacao'));
        $para = $emprestimo->situacao;

        if (! in_array($para, self::TRANSICOES_VALIDAS[$de->value], true)) {
            throw new DomainException('Transição de situação inválida');
        }

        if ($emprestimo->qtd_renovacoes > Emprestimo::MAX_RENOVACOES) {
            throw new DomainException('O máximo de renovações foi atingido.');
        }
    }
}
