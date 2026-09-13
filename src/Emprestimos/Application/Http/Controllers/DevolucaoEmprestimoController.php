<?php

namespace Emprestimos\Application\Http\Controllers;

use App\Http\Controllers\Controller;
use Emprestimos\Application\Http\Requests\DevolverEmprestimoRequest;
use Emprestimos\Domain\Models\Emprestimo;
use Emprestimos\Domain\Services\DevolucaoEmprestimoService;
use Illuminate\Http\JsonResponse;

class DevolucaoEmprestimoController extends Controller
{
    public function __invoke(
        DevolverEmprestimoRequest $request,
        Emprestimo $emprestimo,
        DevolucaoEmprestimoService $service
    ): JsonResponse {
        $service->devolver($emprestimo);

        return response()->json($emprestimo->fresh());
    }
}
