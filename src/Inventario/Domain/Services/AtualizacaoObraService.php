<?php

namespace Inventario\Domain\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Inventario\Domain\Models\Obra;
use Inventario\Domain\ValueObjects\Isbn;

class AtualizacaoObraService
{
    public function __construct(
        private readonly ArmazenadorCapaObra $armazenadorCapa,
    ) {}

    public function atualizar(Obra $obra, array $dados, ?UploadedFile $capa = null): Obra
    {
        $capaAnterior = $obra->capa_path;

        $obra = DB::transaction(function () use ($obra, $dados, $capa): Obra {
            $obra->fill([
                'titulo' => $dados['titulo'],
                'isbn' => isset($dados['isbn']) ? Isbn::fromNative($dados['isbn']) : null,
                'editora_id' => $dados['editora_id'],
                'categoria_id' => $dados['categoria_id'],
                'ano_publicacao' => $dados['ano_publicacao'] ?? null,
            ]);

            if ($capa !== null) {
                $obra->capa_path = $this->armazenadorCapa->armazenar($capa, $obra);
            }

            $obra->save();
            $obra->definirAutores($dados['autores'], $obra->user_id);

            return $obra;
        });

        if ($capa !== null && $capaAnterior !== null) {
            $this->armazenadorCapa->remover($capaAnterior);
        }

        return $obra;
    }
}
