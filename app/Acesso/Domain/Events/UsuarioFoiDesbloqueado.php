<?php

namespace App\Acesso\Domain\Events;

use App\Events\DomainEvent;
use App\Models\User;
use Illuminate\Queue\SerializesModels;

final readonly class UsuarioFoiDesbloqueado implements DomainEvent
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
