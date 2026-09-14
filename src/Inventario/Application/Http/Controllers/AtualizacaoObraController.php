<?php

namespace Inventario\Application\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Inventario\Application\Http\Requests\AtualizarObraRequest;
use Inventario\Domain\Models\Obra;
use Inventario\Domain\Services\AtualizacaoObraService;

class AtualizacaoObraController extends Controller
{
    public function __invoke(AtualizarObraRequest $request, Obra $obra, AtualizacaoObraService $service): JsonResponse
    {
        $obra = $service->atualizar($obra, $request->validated(), $request->file('capa'));

        return response()->json($obra->load('autores'));
    }
}
