<?php

namespace Database\Factories;

use Acesso\Domain\Models\User;
use Carbon\CarbonImmutable;
use Emprestimos\Domain\Enums\ReservaSituacao;
use Emprestimos\Domain\Models\Reserva;
use Emprestimos\Domain\ValueObjects\JanelaReserva;
use Illuminate\Database\Eloquent\Factories\Factory;
use Inventario\Domain\Models\Obra;

/**
 * @extends Factory<Reserva>
 */
class ReservaFactory extends Factory
{
    protected $model = Reserva::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'obra_id' => Obra::factory(),
            'enfileirada_em' => CarbonImmutable::now(),
            'situacao' => ReservaSituacao::Aguardando,
        ];
    }

    public function enfileiradaEm(CarbonImmutable $em): static
    {
        return $this->state(fn (array $attributes) => [
            'enfileirada_em' => $em,
            'created_at' => $em,
            'updated_at' => $em,
        ]);
    }

    public function disponivel(int $exemplarId, ?CarbonImmutable $desde = null): static
    {
        return $this->state(fn (array $attributes) => [
            'situacao' => ReservaSituacao::Disponivel,
            'exemplar_id' => $exemplarId,
            'janela' => JanelaReserva::abrir($desde ?? CarbonImmutable::now()),
        ]);
    }

    public function atendida(int $exemplarId, ?CarbonImmutable $em = null): static
    {
        $em ??= CarbonImmutable::now();

        return $this->disponivel($exemplarId, $em->subDay())
            ->state(fn (array $attributes) => [
                'situacao' => ReservaSituacao::Atendida,
                'updated_at' => $em,
            ]);
    }

    public function expirada(int $exemplarId, ?CarbonImmutable $em = null): static
    {
        $em ??= CarbonImmutable::now();

        return $this->disponivel($exemplarId, $em->subHours(48))
            ->state(fn (array $attributes) => [
                'situacao' => ReservaSituacao::Expirada,
                'updated_at' => $em,
            ]);
    }

    public function cancelada(?CarbonImmutable $em = null): static
    {
        return $this->state(fn (array $attributes) => [
            'situacao' => ReservaSituacao::Cancelada,
            'updated_at' => $em ?? CarbonImmutable::now(),
        ]);
    }
}
