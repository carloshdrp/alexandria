<?php

namespace Emprestimos\Application\Http\Controllers;

use App\Http\Controllers\Controller;
use Emprestimos\Application\Http\Requests\RenovarEmprestimoRequest;
use Emprestimos\Domain\Models\Emprestimo;
use Emprestimos\Domain\Services\RenovacaoEmprestimoService;
use Illuminate\Http\JsonResponse;

class RenovacaoEmprestimoController extends Controller
{
    public function __invoke(
        RenovarEmprestimoRequest $request,
        Emprestimo $emprestimo,
        RenovacaoEmprestimoService $service
    ): JsonResponse {
        $service->renovar($emprestimo);

        return response()->json($emprestimo->fresh());
    }
}
