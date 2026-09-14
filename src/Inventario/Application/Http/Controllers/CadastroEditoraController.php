<?php

namespace Inventario\Application\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Inventario\Application\Http\Requests\CadastrarEditoraRequest;
use Inventario\Domain\Models\Editora;
use Symfony\Component\HttpFoundation\Response;

class CadastroEditoraController extends Controller
{
    public function __invoke(CadastrarEditoraRequest $request): JsonResponse
    {
        $editora = Editora::create($request->validated());

        return response()->json($editora, Response::HTTP_CREATED);
    }
}
