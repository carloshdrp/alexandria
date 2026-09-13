<?php

namespace Emprestimos\Application\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RealizarEmprestimoRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'exemplar_id' => ['required', 'integer', 'exists:exemplares,id'],
        ];
    }

    public function authorize(): bool
    {
        return true; // TODO: Restringir a bibliotecários quando o auth for implementado
    }
}
