<?php

namespace Emprestimos\Application\Policies;

use Acesso\Domain\Models\User;
use Emprestimos\Domain\Models\Reserva;

class ReservaPolicy
{
    public function cancelar(User $user, Reserva $reserva): bool
    {
        return $this->ver($user, $reserva);
    }

    public function ver(User $user, Reserva $reserva): bool
    {
        return $user->ehBibliotecario() || $reserva->user_id === $user->id;
    }

    public function criarParaOutro(User $user): bool
    {
        return $user->ehBibliotecario();
    }

    public function atender(User $user): bool
    {
        return $user->ehBibliotecario();
    }
}
