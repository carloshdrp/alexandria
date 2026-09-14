<?php

namespace Inventario\Application\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CadastrarEditoraRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'nome' => ['required', 'string', 'max:255', 'unique:editoras,nome'],
            'user_id' => ['required', 'integer', 'exists:users,id'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
