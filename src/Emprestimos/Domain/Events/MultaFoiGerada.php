<?php

namespace Emprestimos\Domain\Events;

use App\Events\DomainEvent;
use Emprestimos\Domain\Models\Multa;
use Illuminate\Queue\SerializesModels;

final readonly class MultaFoiGerada implements DomainEvent
{
    use SerializesModels;

    public function __construct(
        public Multa $multa,
    ) {}

    public function entityKey(): int
    {
        return $this->multa->id;
    }
}
