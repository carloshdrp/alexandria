<?php

namespace App\Acesso\Application\Actions;

trait NormalizaDocumentos
{
    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    protected function normalizar(array $input): array
    {
        foreach (['documento', 'telefone'] as $campo) {
            if (! isset($input[$campo]) || ! is_string($input[$campo])) {
                continue;
            }

            $digitos = preg_replace('/\D/', '', $input[$campo]) ?? '';

            $input[$campo] = $digitos === '' ? null : $digitos;
        }

        return $input;
    }
}
