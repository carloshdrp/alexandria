<?php

namespace Inventario\Domain\Events;

use App\Events\DomainEvent;
use Illuminate\Queue\SerializesModels;
use Inventario\Domain\Models\Obra;

final readonly class ObraFoiAtualizada implements DomainEvent
{
    use SerializesModels;

    public function __construct(
        public Obra $obra,
    ) {}

    public function entityKey(): int
    {
        return $this->obra->id;
    }
}
