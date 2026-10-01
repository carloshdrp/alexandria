<?php

namespace Emprestimos\Domain\Events;

use App\Events\DomainEvent;
use Emprestimos\Domain\Models\Reserva;
use Illuminate\Queue\SerializesModels;

final readonly class ReservaFoiCancelada implements DomainEvent
{
    use SerializesModels;

    public function __construct(
        public Reserva $reserva,
    ) {}

    public function entityKey(): int
    {
        return $this->reserva->id;
    }
}
