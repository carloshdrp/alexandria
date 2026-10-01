<?php

namespace Emprestimos\Domain\Events;

use App\Events\DomainEvent;
use Emprestimos\Domain\Models\Emprestimo;
use Illuminate\Queue\SerializesModels;

final readonly class EmprestimoFoiAtrasado implements DomainEvent
{
    use SerializesModels;

    public function __construct(
        public Emprestimo $emprestimo,
    ) {}

    public function entityKey(): int
    {
        return $this->emprestimo->id;
    }
}
