<?php

namespace Inventario\Infrastructure\Storage;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inventario\Domain\Contracts\ArmazenadorCapaObra;
use Inventario\Domain\Models\Obra;

class ArmazenadorCapaObraEmDisco implements ArmazenadorCapaObra
{
    private const string DIRETORIO = 'capas/obras';

    public function armazenar(UploadedFile $arquivo, Obra $obra): string
    {
        $nome = Str::uuid()->toString().'.'.$arquivo->extension();

        return $this->disco()->putFileAs(
            self::DIRETORIO.'/'.$obra->getKey(),
            $arquivo,
            $nome,
        );
    }

    public function remover(string $path): void
    {
        $this->disco()->delete($path);
    }

    public function url(string $path): string
    {
        return $this->disco()->url($path);
    }

    private function disco(): Filesystem
    {
        return Storage::disk('public');
    }
}
