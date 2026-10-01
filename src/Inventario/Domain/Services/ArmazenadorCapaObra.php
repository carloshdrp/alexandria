<?php

namespace Inventario\Domain\Services;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inventario\Domain\Models\Obra;
use RuntimeException;

class ArmazenadorCapaObra
{
    private const string DIRETORIO = 'capas/obras';

    public function armazenar(UploadedFile $arquivo, Obra $obra): string
    {
        $nome = Str::uuid()->toString().'.'.$arquivo->extension();

        $path = $this->disco()->putFileAs(
            self::DIRETORIO.'/'.$obra->getKey(),
            $arquivo,
            $nome,
        );

        if ($path === false) {
            throw new RuntimeException("Não foi possível armazenar a capa da obra {$obra->getKey()}.");
        }

        return $path;
    }

    private function disco(): Filesystem
    {
        return Storage::disk('public');
    }

    public function remover(string $path): void
    {
        $this->disco()->delete($path);
    }

    public function url(string $path): string
    {
        return $this->disco()->url($path);
    }
}
