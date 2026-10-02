<?php

namespace Database\Factories;

use Carbon\CarbonImmutable;
use Emprestimos\Domain\Enums\MultaSituacao;
use Emprestimos\Domain\Models\Emprestimo;
use Emprestimos\Domain\Models\Multa;
use Emprestimos\Domain\ValueObjects\ValorMulta;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Multa>
 */
class MultaFactory extends Factory
{
    protected $model = Multa::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $dias = fake()->numberBetween(1, 10);

        return [
            'emprestimo_id' => Emprestimo::factory()->atrasado()->devolvido(),
            'user_id' => fn (array $attributes) => Emprestimo::query()->whereKey($attributes['emprestimo_id'])->firstOrFail()->user_id,
            'dias_atraso' => $dias,
            'valor' => ValorMulta::calcular($dias)->total(),
            'situacao' => MultaSituacao::Pendente,
        ];
    }

    public function doEmprestimo(Emprestimo $emprestimo): static
    {
        $dias = $emprestimo->prazo->diasAtraso($emprestimo->devolvido_em);

        return $this->state(fn (array $attributes) => [
            'emprestimo_id' => $emprestimo->id,
            'user_id' => $emprestimo->user_id,
            'dias_atraso' => $dias,
            'valor' => ValorMulta::calcular($dias)->total(),
            'created_at' => $emprestimo->devolvido_em,
            'updated_at' => $emprestimo->devolvido_em,
        ]);
    }

    public function paga(?CarbonImmutable $em = null): static
    {
        return $this->state(fn (array $attributes) => [
            'situacao' => MultaSituacao::Paga,
            'paga_em' => $em ?? CarbonImmutable::now(),
        ]);
    }
}
