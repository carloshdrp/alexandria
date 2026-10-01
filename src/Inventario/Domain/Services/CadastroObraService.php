<?php

namespace Inventario\Domain\Services;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Inventario\Domain\Events\ObraFoiCadastrada;
use Inventario\Domain\Models\Obra;
use Inventario\Domain\ValueObjects\Isbn;

class CadastroObraService
{
    public function __construct(
        private readonly ArmazenadorCapaObra $armazenadorCapa,
    ) {}

    /**
     * @param  array<string, mixed>  $dados
     */
    public function cadastrar(User $user, array $dados, ?UploadedFile $capa = null): Obra
    {
        $obra = DB::transaction(function () use ($user, $dados, $capa): Obra {
            $obra = Obra::create([
                'titulo' => $dados['titulo'],
                'isbn' => isset($dados['isbn']) ? Isbn::fromNative($dados['isbn']) : null,
                'editora_id' => $dados['editora_id'],
                'categoria_id' => $dados['categoria_id'],
                'ano_publicacao' => $dados['ano_publicacao'] ?? null,
                'user_id' => $user->id,
            ]);

            $obra->definirAutores($dados['autores'], $user->id);

            if ($capa !== null) {
                $obra->capa_path = $this->armazenadorCapa->armazenar($capa, $obra);
                $obra->save();
            }

            return $obra;
        });

        event(new ObraFoiCadastrada($obra));

        return $obra;
    }
}
