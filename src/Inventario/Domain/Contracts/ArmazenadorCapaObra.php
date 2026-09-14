<?php

namespace Inventario\Domain\Contracts;

use Illuminate\Http\UploadedFile;
use Inventario\Domain\Models\Obra;

interface ArmazenadorCapaObra
{
    public function armazenar(UploadedFile $arquivo, Obra $obra): string;

    public function remover(string $path): void;

    public function url(string $path): string;
}
