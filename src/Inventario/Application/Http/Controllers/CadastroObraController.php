<?php

namespace Inventario\Application\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Inventario\Application\Http\Requests\CadastrarObraRequest;
use Inventario\Domain\Services\CadastroObraService;
use Symfony\Component\HttpFoundation\Response;

class CadastroObraController extends Controller
{
    public function __invoke(CadastrarObraRequest $request, CadastroObraService $service): JsonResponse
    {
        $user = User::findOrFail($request->validated('user_id'));

        $obra = $service->cadastrar($user, $request->validated(), $request->file('capa'));

        return response()->json($obra->load('autores'), Response::HTTP_CREATED);
    }
}
