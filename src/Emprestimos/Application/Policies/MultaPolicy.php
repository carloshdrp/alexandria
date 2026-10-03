<?php

namespace Emprestimos\Application\Policies;

use Acesso\Domain\Models\User;
use Emprestimos\Domain\Models\Multa;

class MultaPolicy
{
    public function ver(User $user, Multa $multa): bool
    {
        return $user->ehBibliotecario() || $multa->user_id === $user->id;
    }

    public function pagar(User $user): bool
    {
        return $user->ehBibliotecario();
    }
}
