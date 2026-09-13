<?php

namespace Emprestimos\Infrastructure\Casts;

use Carbon\CarbonImmutable;
use Emprestimos\Domain\ValueObjects\JanelaReserva;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

class JanelaReservaCast implements CastsAttributes
{
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

    public function set(Model $model, string $key, mixed $value, array $attributes): array
    {
        if (! ($value instanceof JanelaReserva)) {
            throw new InvalidArgumentException("O atributo '$key' deve ser uma instância de ".JanelaReserva::class.'.');
        }

        return [
            'disponibilizada_em' => $value->disponibilizadaEm(),
            'expira_em' => $value->expiraEm(),
        ];
    }
}
