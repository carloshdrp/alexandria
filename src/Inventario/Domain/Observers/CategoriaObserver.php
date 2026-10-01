<?php

namespace Inventario\Domain\Observers;

use DomainException;
use Inventario\Domain\Models\Categoria;
use Inventario\Domain\Models\Obra;

class CategoriaObserver
{
    public function deleting(Categoria $categoria): void
    {
        if (Obra::daCategoria($categoria->id)->exists()) {
            throw new DomainException('Categoria está vinculada a obras do acervo.');
        }
    }
}
