<?php

namespace Acesso\Domain\Events;

use Acesso\Domain\Models\User;
use App\Events\DomainEvent;
use Illuminate\Queue\SerializesModels;

final readonly class UsuarioFoiBloqueado implements DomainEvent
{
    use SerializesModels;

    public function __construct(
        public User $user,
    ) {}

    public function entityKey(): int
    {
        return $this->user->id;
    }
}
