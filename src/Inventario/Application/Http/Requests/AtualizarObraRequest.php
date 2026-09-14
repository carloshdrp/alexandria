<?php

namespace Inventario\Application\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Inventario\Infrastructure\Rules\IsbnValido;

class AtualizarObraRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'titulo' => ['required', 'string', 'max:255'],
            'isbn' => ['nullable', 'string', new IsbnValido, Rule::unique('obras', 'isbn')->ignore($this->route('obra'))],
            'editora_id' => ['required', 'integer', 'exists:editoras,id'],
            'categoria_id' => ['required', 'integer', 'exists:categorias,id'],
            'ano_publicacao' => ['nullable', 'integer', 'min:1450', 'max:'.date('Y')],
            'autores' => ['required', 'array', 'min:1'],
            'autores.*' => ['integer', 'distinct', 'exists:autores,id'],
            'capa' => ['nullable', 'image', 'mimes:jpeg,png,webp', 'max:5120'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
