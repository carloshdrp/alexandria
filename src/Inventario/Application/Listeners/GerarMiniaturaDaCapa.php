<?php

namespace Inventario\Application\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Inventario\Domain\Events\ObraFoiAtualizada;
use Inventario\Domain\Events\ObraFoiCadastrada;
use Inventario\Domain\Models\Obra;
use Inventario\Domain\Services\ArmazenadorCapaObra;

class GerarMiniaturaDaCapa implements ShouldQueue
{
    public int $tries = 5;

    public int $backoff = 60;

    public function __construct(
        private readonly ArmazenadorCapaObra $armazenador,
    ) {}

    public function __invoke(ObraFoiCadastrada|ObraFoiAtualizada $evento): void
    {
        $obra = $evento->obra;

        if ($obra->capa_path === null) {
            $this->esquecerMiniatura($obra);

            return;
        }

        if ($obra->capa_miniatura_path === $this->armazenador->caminhoDaMiniatura($obra->capa_path)) {
            return;
        }

        $obra->capa_miniatura_path = $this->armazenador->gerarMiniatura($obra);
        $obra->save();
    }

    private function esquecerMiniatura(Obra $obra): void
    {
        if ($obra->capa_miniatura_path === null) {
            return;
        }

        $obra->capa_miniatura_path = null;
        $obra->save();
    }
}
