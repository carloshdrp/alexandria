<?php

namespace Inventario\Application\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Inventario\Domain\Enums\ExemplarEstadoConservacao;
use Inventario\Infrastructure\Rules\CodigoPatrimonioValido;

class CadastrarExemplarRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'codigo_patrimonio' => ['required', 'string', new CodigoPatrimonioValido, 'unique:exemplares,codigo_patrimonio'],
            'estado_conservacao' => ['required', Rule::enum(ExemplarEstadoConservacao::class)],
            'user_id' => ['required', 'integer', 'exists:users,id'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
