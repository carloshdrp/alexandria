<?php

namespace Database\Factories;

use Carbon\CarbonImmutable;
use Emprestimos\Domain\Models\Emprestimo;
use Emprestimos\Domain\Models\EmprestimoRenovacao;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EmprestimoRenovacao>
 */
class EmprestimoRenovacaoFactory extends Factory
{
    protected $model = EmprestimoRenovacao::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $prazoAnterior = CarbonImmutable::now()->subDays(1);

        return [
            'emprestimo_id' => Emprestimo::factory()->renovado(),
            'user_id' => fn (array $attributes) => Emprestimo::query()->whereKey($attributes['emprestimo_id'])->firstOrFail()->user_id,
            'sequencia' => 1,
            'prazo_anterior' => $prazoAnterior,
            'prazo_novo' => $prazoAnterior->addDays(14),
            'renovado_em' => $prazoAnterior,
        ];
    }

    public function doEmprestimo(Emprestimo $emprestimo, int $sequencia = 1): static
    {
        $prazoNovo = $emprestimo->prazo->prazoDevolucao();
        $prazoAnterior = $prazoNovo->subDays(14 * ($emprestimo->qtd_renovacoes - $sequencia + 1));

        return $this->state(fn (array $attributes) => [
            'emprestimo_id' => $emprestimo->id,
            'user_id' => $emprestimo->user_id,
            'sequencia' => $sequencia,
            'prazo_anterior' => $prazoAnterior,
            'prazo_novo' => $prazoAnterior->addDays(14),
            'renovado_em' => $prazoAnterior->subDay(),
        ]);
    }
}
