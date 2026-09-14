<?php

namespace Inventario\Application\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Inventario\Application\Http\Requests\CadastrarAutorRequest;
use Inventario\Domain\Models\Autor;
use Symfony\Component\HttpFoundation\Response;

class CadastroAutorController extends Controller
{
    public function __invoke(CadastrarAutorRequest $request): JsonResponse
    {
        $autor = Autor::create($request->validated());

        return response()->json($autor, Response::HTTP_CREATED);
    }
}
