<?php

namespace Emprestimos\Application\Http\Controllers;

use App\Http\Controllers\Controller;
use Emprestimos\Application\Http\Requests\PagarMultaRequest;
use Emprestimos\Domain\Models\Multa;
use Illuminate\Http\JsonResponse;

class PagamentoMultaController extends Controller
{
    public function __invoke(PagarMultaRequest $request, Multa $multa): JsonResponse
    {
        $multa->pagar();
        $multa->save();

        return response()->json($multa->fresh());
    }
}
