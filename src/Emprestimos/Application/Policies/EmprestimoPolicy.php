<?php

namespace Emprestimos\Application\Policies;

use App\Models\User;
use Emprestimos\Domain\Models\Emprestimo;

class EmprestimoPolicy
{
    public function renovar(User $user, Emprestimo $emprestimo): bool
    {
        return $this->ver($user, $emprestimo);
    }

    public function ver(User $user, Emprestimo $emprestimo): bool
    {
        return $user->ehBibliotecario() || $emprestimo->user_id === $user->id;
    }

    public function devolver(User $user): bool
    {
        return $user->ehBibliotecario();
    }
}
