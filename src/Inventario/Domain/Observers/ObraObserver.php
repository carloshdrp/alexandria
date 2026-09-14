<?php

namespace Inventario\Domain\Observers;

use DomainException;
use Inventario\Domain\Models\Exemplar;
use Inventario\Domain\Models\Obra;

class ObraObserver
{
    public function deleting(Obra $obra): void
    {
        if (Exemplar::naoBaixadosPorObra($obra->id)->exists()) {
            throw new DomainException('Obra possui exemplares que ainda não foram baixados.');
        }
    }
}
