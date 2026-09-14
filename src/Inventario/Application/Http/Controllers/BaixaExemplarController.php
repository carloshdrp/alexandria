<?php

namespace Inventario\Application\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Inventario\Application\Http\Requests\BaixarExemplarRequest;
use Inventario\Domain\Enums\ExemplarMotivoBaixa;
use Inventario\Domain\Models\Exemplar;

class BaixaExemplarController extends Controller
{
    public function __invoke(BaixarExemplarRequest $request, Exemplar $exemplar): JsonResponse
    {
        $exemplar->baixar(ExemplarMotivoBaixa::from((int) $request->validated('motivo_baixa')));
        $exemplar->save();

        return response()->json($exemplar->fresh());
    }
}
