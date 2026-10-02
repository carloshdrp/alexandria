<?php

namespace Emprestimos\Infrastructure\Casts;

use Carbon\CarbonImmutable;
use Emprestimos\Domain\ValueObjects\JanelaReserva;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * @implements CastsAttributes<JanelaReserva, JanelaReserva>
 */
class JanelaReservaCast implements CastsAttributes
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?JanelaReserva
    {
        if (! isset($attributes['disponibilizada_em'], $attributes['expira_em'])) {
            return null;
        }

        return JanelaReserva::reconstruir(
            CarbonImmutable::parse($attributes['disponibilizada_em']),
            CarbonImmutable::parse($attributes['expira_em']),
        );
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, CarbonImmutable|null>
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): array
    {
        return [
            'disponibilizada_em' => $value?->disponibilizadaEm(),
            'expira_em' => $value?->expiraEm(),
        ];
    }
}
