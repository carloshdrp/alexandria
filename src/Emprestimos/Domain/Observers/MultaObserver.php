<?php

namespace Emprestimos\Domain\Observers;

use DomainException;
use Emprestimos\Domain\Enums\MultaSituacao;
use Emprestimos\Domain\Models\Multa;

class MultaObserver
{
    private const array TRANSICOES_VALIDAS = [
        MultaSituacao::Pendente->value => [MultaSituacao::Paga],
        MultaSituacao::Paga->value => [],
    ];

    public function saving(Multa $multa): void
    {
        if (! $multa->exists()) {
            return;
        }

        if ($multa->isDirty('situacao')) {
            $de = MultaSituacao::from($multa->getRawOriginal('situacao'));
            $para = $multa->situacao;

            if (! in_array($para, self::TRANSICOES_VALIDAS[$de->value], true)) {
                throw new DomainException('Transição de situação inválida');
            }
        }

        if ($multa->isDirty(['valor', 'dias_atraso'])) {
            throw new DomainException('Não é possível alterar o valor ou dias de atraso após o registro da multa.');
        }
    }
}
