<?php

namespace Database\Factories;

use Acesso\Domain\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\Factory;
use Inventario\Domain\Enums\ExemplarEstadoConservacao;
use Inventario\Domain\Enums\ExemplarMotivoBaixa;
use Inventario\Domain\Enums\ExemplarSituacao;
use Inventario\Domain\Models\Exemplar;
use Inventario\Domain\Models\Obra;
use Inventario\Domain\ValueObjects\CodigoPatrimonio;

/**
 * @extends Factory<Exemplar>
 */
class ExemplarFactory extends Factory
{
    protected $model = Exemplar::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'obra_id' => Obra::factory(),
            'codigo_patrimonio' => CodigoPatrimonio::fromNative('EX-'.fake()->unique()->numerify('######')),
            'estado_conservacao' => fake()->randomElement(ExemplarEstadoConservacao::cases()),
            'situacao' => ExemplarSituacao::NoAcervo,
            'user_id' => User::factory()->bibliotecario(),
        ];
    }

    public function emprestado(): static
    {
        return $this->state(fn (array $attributes) => [
            'situacao' => ExemplarSituacao::Emprestado,
        ]);
    }

    public function reservado(): static
    {
        return $this->state(fn (array $attributes) => [
            'situacao' => ExemplarSituacao::Reservado,
        ]);
    }

    public function baixado(ExemplarMotivoBaixa $motivo, ?CarbonImmutable $em = null): static
    {
        return $this->state(fn (array $attributes) => [
            'situacao' => ExemplarSituacao::Baixado,
            'motivo_baixa' => $motivo,
            'baixado_em' => $em ?? CarbonImmutable::now(),
        ]);
    }
}
