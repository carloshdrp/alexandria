<?php

namespace Emprestimos\Infrastructure\Casts;

use Carbon\CarbonImmutable;
use Emprestimos\Domain\ValueObjects\PrazoEmprestimo;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

/**
 * @implements CastsAttributes<PrazoEmprestimo, PrazoEmprestimo>
 */
class PrazoEmprestimoCast implements CastsAttributes
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?PrazoEmprestimo
    {
        if (! isset($attributes['retirado_em'], $attributes['prazo_devolucao'])) {
            return null;
        }

        return PrazoEmprestimo::reconstruir(
            CarbonImmutable::parse($attributes['retirado_em']),
            CarbonImmutable::parse($attributes['prazo_devolucao']),
        );
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, CarbonImmutable>
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): array
    {
        if (! ($value instanceof PrazoEmprestimo)) {
            throw new InvalidArgumentException("O atributo '$key' deve ser uma instância de ".PrazoEmprestimo::class.'.');
        }

        return [
            'retirado_em' => $value->retiradoEm(),
            'prazo_devolucao' => $value->prazoDevolucao(),
        ];
    }
}
