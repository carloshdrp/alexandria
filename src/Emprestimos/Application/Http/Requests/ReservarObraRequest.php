<?php

namespace Emprestimos\Application\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReservarObraRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'obra_id' => ['required', 'integer', 'exists:obras,id'],
        ];
    }

    public function authorize(): bool
    {
        return true; // TODO
    }
}
