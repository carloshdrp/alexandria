<?php

namespace Inventario\Application\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Inventario\Application\Http\Requests\CadastrarCategoriaRequest;
use Inventario\Domain\Models\Categoria;
use Symfony\Component\HttpFoundation\Response;

class CadastroCategoriaController extends Controller
{
    public function __invoke(CadastrarCategoriaRequest $request): JsonResponse
    {
        $categoria = Categoria::create($request->validated());

        return response()->json($categoria, Response::HTTP_CREATED);
    }
}
