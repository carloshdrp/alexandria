<?php

namespace Inventario\Domain\Events\Integracao;

use App\Events\IntegrationEvent;

final readonly class ExemplarFoiBaixado implements IntegrationEvent
{
    public function __construct(
        public int $exemplarId,
        public int $obraId,
    ) {}

    public function entityKey(): int
    {
        return $this->exemplarId;
    }
}
