<?php

namespace Inventario\Domain\Observers;

use DomainException;
use Inventario\Domain\Models\Autor;
use Inventario\Domain\Models\ObraAutor;

class AutorObserver
{
    public function deleting(Autor $autor): void
    {
        if (ObraAutor::doAutor($autor->id)->exists()) {
            throw new DomainException('Autor está vinculado a obras do acervo.');
        }
    }
}
