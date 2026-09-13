<?php

namespace Inventario\Domain\Observers;

use DomainException;
use Inventario\Domain\Enums\ExemplarSituacao;
use Inventario\Domain\Models\Exemplar;

class ExemplarObserver
{
    private const array TRANSICOES_VALIDAS = [
        ExemplarSituacao::NoAcervo->value => [ExemplarSituacao::Baixado->value, ExemplarSituacao::Emprestado->value],
        ExemplarSituacao::Emprestado->value => [ExemplarSituacao::Baixado->value, ExemplarSituacao::NoAcervo->value],
        ExemplarSituacao::Baixado->value => [],
    ];

    public function saving(Exemplar $exemplar): void
    {
        if (! $exemplar->exists || ! $exemplar->isDirty('situacao')) {
            return;
        }

        $de = ExemplarSituacao::from($exemplar->getRawOriginal('situacao'));
        $para = $exemplar->situacao;

        if (! in_array($para, self::TRANSICOES_VALIDAS[$de->value])) {
            throw new DomainException('Transição de situação inválida');
        }
    }
}
