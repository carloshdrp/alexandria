<?php

namespace Inventario\Domain\Observers;

use DomainException;
use Inventario\Domain\Models\Editora;
use Inventario\Domain\Models\Obra;

class EditoraObserver
{
    public function deleting(Editora $editora): void
    {
        if (Obra::daEditora($editora->id)->exists()) {
            throw new DomainException('Editora está vinculada a obras do acervo.');
        }
    }
}
