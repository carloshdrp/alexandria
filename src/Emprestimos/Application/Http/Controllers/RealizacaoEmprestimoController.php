<?php

namespace Emprestimos\Application\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Emprestimos\Application\Http\Requests\RealizarEmprestimoRequest;
use Emprestimos\Domain\Services\RealizacaoEmprestimoService;
use Illuminate\Http\JsonResponse;
use Inventario\Domain\Models\Exemplar;
use Symfony\Component\HttpFoundation\Response;

class RealizacaoEmprestimoController extends Controller
{
    public function __invoke(RealizarEmprestimoRequest $request, RealizacaoEmprestimoService $service): JsonResponse
    {
        $user = User::findOrFail($request->validated('user_id'));
        $exemplar = Exemplar::findOrFail($request->validated('exemplar_id'));

        $emprestimo = $service->realizar($user, $exemplar);

        return response()->json($emprestimo, Response::HTTP_CREATED);
    }
}
