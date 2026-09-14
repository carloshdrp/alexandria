<?php

namespace Inventario\Application\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Inventario\Application\Http\Requests\CadastrarExemplarRequest;
use Inventario\Domain\Enums\ExemplarEstadoConservacao;
use Inventario\Domain\Models\Exemplar;
use Inventario\Domain\Models\Obra;
use Inventario\Domain\ValueObjects\CodigoPatrimonio;
use Symfony\Component\HttpFoundation\Response;

class CadastroExemplarController extends Controller
{
    public function __invoke(CadastrarExemplarRequest $request, Obra $obra): JsonResponse
    {
        $exemplar = Exemplar::create([
            'obra_id' => $obra->id,
            'codigo_patrimonio' => CodigoPatrimonio::fromNative($request->validated('codigo_patrimonio')),
            'estado_conservacao' => ExemplarEstadoConservacao::from((int) $request->validated('estado_conservacao')),
            'user_id' => $request->validated('user_id'),
        ]);

        return response()->json($exemplar->refresh(), Response::HTTP_CREATED);
    }
}
