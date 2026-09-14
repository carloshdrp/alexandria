<?php

namespace Inventario\Application\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Inventario\Domain\Enums\ExemplarMotivoBaixa;

class BaixarExemplarRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'motivo_baixa' => ['required', Rule::enum(ExemplarMotivoBaixa::class)],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
