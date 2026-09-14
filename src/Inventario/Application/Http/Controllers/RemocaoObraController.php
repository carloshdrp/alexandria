<?php

namespace Inventario\Application\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Inventario\Application\Http\Requests\RemoverObraRequest;
use Inventario\Domain\Models\Obra;
use Symfony\Component\HttpFoundation\Response;

class RemocaoObraController extends Controller
{
    public function __invoke(RemoverObraRequest $request, Obra $obra): JsonResponse
    {
        $obra->delete();

        return response()->json(status: Response::HTTP_NO_CONTENT);
    }
}
