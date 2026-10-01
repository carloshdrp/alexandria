<?php

namespace Emprestimos\Domain\Observers;

use DomainException;
use Emprestimos\Domain\Enums\ReservaSituacao;
use Emprestimos\Domain\Models\Reserva;

class ReservaObserver
{
    private const array TRANSICOES_VALIDAS = [
        ReservaSituacao::Aguardando->value => [ReservaSituacao::Disponivel, ReservaSituacao::Cancelada],
        ReservaSituacao::Disponivel->value => [ReservaSituacao::Atendida, ReservaSituacao::Expirada, ReservaSituacao::Cancelada, ReservaSituacao::Aguardando],
        ReservaSituacao::Atendida->value => [],
        ReservaSituacao::Expirada->value => [],
        ReservaSituacao::Cancelada->value => [],
    ];

    public function saving(Reserva $reserva): void
    {
        if (! $reserva->exists || ! $reserva->isDirty('situacao')) {
            return;
        }

        $de = ReservaSituacao::from($reserva->getRawOriginal('situacao'));
        $para = $reserva->situacao;

        if (! in_array($para, self::TRANSICOES_VALIDAS[$de->value], true)) {
            throw new DomainException('Transição de situação inválida');
        }
    }
}
