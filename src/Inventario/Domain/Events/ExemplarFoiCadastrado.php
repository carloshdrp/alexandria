<?php

namespace Inventario\Domain\Events;

use App\Events\DomainEvent;
use Illuminate\Queue\SerializesModels;
use Inventario\Domain\Models\Exemplar;

final readonly class ExemplarFoiCadastrado implements DomainEvent
{
    use SerializesModels;

    public function __construct(
        public Exemplar $exemplar,
    ) {}

    public function entityKey(): int
    {
        return $this->exemplar->id;
    }
}
