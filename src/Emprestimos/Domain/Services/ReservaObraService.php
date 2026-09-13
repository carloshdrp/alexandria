<?php

namespace Emprestimos\Domain\Services;

use App\Models\User;
use DomainException;
use Emprestimos\Domain\Models\Reserva;
use Inventario\Domain\Models\Exemplar;
use Inventario\Domain\Models\Obra;

class ReservaObraService
{
    public function __construct() {}

    public function reservar(User $user, Obra $obra): Reserva
    {
        if (Exemplar::disponivelPorObra($obra->id)->exists()) {
            throw new DomainException('Já existem exemplares disponíveis para esta obra');
        }

        return Reserva::create([
            'user_id' => $user->id,
            'obra_id' => $obra->id,
        ]);
    }
}
