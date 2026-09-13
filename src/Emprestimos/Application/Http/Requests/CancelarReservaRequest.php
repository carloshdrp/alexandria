<?php

namespace Emprestimos\Application\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CancelarReservaRequest extends FormRequest
{
    public function rules(): array
    {
        return [

        ];
    }

    public function authorize(): bool
    {
        return true; // TODO
    }
}
