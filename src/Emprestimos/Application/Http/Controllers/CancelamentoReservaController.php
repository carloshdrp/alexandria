<?php

namespace Emprestimos\Application\Http\Controllers;

use App\Http\Controllers\Controller;
use Emprestimos\Application\Http\Requests\CancelarReservaRequest;
use Emprestimos\Domain\Models\Reserva;
use Illuminate\Http\JsonResponse;

class CancelamentoReservaController extends Controller
{
    public function __invoke(CancelarReservaRequest $request, Reserva $reserva): JsonResponse
    {
        $reserva->cancelar();
        $reserva->save();

        return response()->json($reserva->fresh());
    }
}
