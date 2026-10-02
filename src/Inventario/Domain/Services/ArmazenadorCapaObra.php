<?php

namespace Inventario\Domain\Services;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Encoders\WebpEncoder;
use Intervention\Image\ImageManager;
use Inventario\Domain\Models\Obra;
use RuntimeException;

class ArmazenadorCapaObra
{
    private const string DIRETORIO = 'capas/obras';

    private const int LARGURA_MINIATURA = 400;

    private const int QUALIDADE_MINIATURA = 80;

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

    public function gerarMiniatura(Obra $obra): string
    {
        if ($obra->capa_path === null) {
            throw new RuntimeException("A obra {$obra->getKey()} não tem capa para miniaturizar.");
        }

        $original = $this->disco()->get($obra->capa_path);

        if ($original === null) {
            throw new RuntimeException("A capa {$obra->capa_path} não está no disco.");
        }

        $miniatura = ImageManager::usingDriver(Driver::class)
            ->decodeBinary($original)
            ->scaleDown(width: self::LARGURA_MINIATURA)
            ->encode(new WebpEncoder(quality: self::QUALIDADE_MINIATURA));

        $path = $this->caminhoDaMiniatura($obra->capa_path);

        $this->disco()->put($path, $miniatura->toString());

        return $path;
    }

    public function caminhoDaMiniatura(string $capaPath): string
    {
        return dirname($capaPath).'/miniaturas/'.pathinfo($capaPath, PATHINFO_FILENAME).'.webp';
    }

    private function disco(): Filesystem
    {
        return Storage::disk('public');
    }

    public function remover(string $path): void
    {
        $this->disco()->delete([$path, $this->caminhoDaMiniatura($path)]);
    }

    public function esvaziar(): void
    {
        $this->disco()->deleteDirectory(self::DIRETORIO);
    }

    public function url(string $path): string
    {
        return $this->disco()->url($path);
    }
}
