<?php

namespace Emprestimos\Application\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Emprestimos\Application\Http\Requests\ReservarObraRequest;
use Emprestimos\Domain\Services\ReservaObraService;
use Illuminate\Http\JsonResponse;
use Inventario\Domain\Models\Obra;
use Symfony\Component\HttpFoundation\Response;

class ReservaObraController extends Controller
{
    public function __invoke(ReservarObraRequest $request, ReservaObraService $service): JsonResponse
    {
        $user = User::findOrFail($request->validated('user_id'));
        $obra = Obra::findOrFail($request->validated('obra_id'));

        $reserva = $service->reservar($user, $obra);

        return response()->json($reserva, Response::HTTP_CREATED);
    }
}
