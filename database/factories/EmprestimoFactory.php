<?php

namespace Database\Factories;

use Acesso\Domain\Models\User;
use Carbon\CarbonImmutable;
use Emprestimos\Domain\Enums\EmprestimoSituacao;
use Emprestimos\Domain\Models\Emprestimo;
use Emprestimos\Domain\ValueObjects\PrazoEmprestimo;
use Illuminate\Database\Eloquent\Factories\Factory;
use InvalidArgumentException;
use Inventario\Domain\Models\Exemplar;

/**
 * @extends Factory<Emprestimo>
 */
class EmprestimoFactory extends Factory
{
    protected $model = Emprestimo::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'exemplar_id' => Exemplar::factory()->emprestado(),
            'prazo' => PrazoEmprestimo::iniciar(CarbonImmutable::now()->subDays(3)),
            'situacao' => EmprestimoSituacao::Andamento,
            'qtd_renovacoes' => 0,
        ];
    }

    public function retiradoEm(CarbonImmutable $retiradoEm): static
    {
        return $this->state(fn (array $attributes) => [
            'prazo' => PrazoEmprestimo::iniciar($retiradoEm),
            'created_at' => $retiradoEm,
            'updated_at' => $retiradoEm,
        ]);
    }

    public function renovado(int $vezes = 1): static
    {
        return $this->state(function (array $attributes) use ($vezes) {
            $prazo = $attributes['prazo'];

            if (! $prazo instanceof PrazoEmprestimo) {
                throw new InvalidArgumentException('O estado renovado() exige um prazo já definido.');
            }

            for ($i = 0; $i < $vezes; $i++) {
                $prazo = $prazo->estender();
            }

            return [
                'prazo' => $prazo,
                'qtd_renovacoes' => $vezes,
            ];
        });
    }

    public function devolvido(?CarbonImmutable $em = null): static
    {
        return $this->state(fn (array $attributes) => [
            'situacao' => EmprestimoSituacao::Devolvido,
            'devolvido_em' => $em ?? CarbonImmutable::now(),
            'updated_at' => $em ?? CarbonImmutable::now(),
        ]);
    }

    public function atrasado(): static
    {
        return $this->retiradoEm(CarbonImmutable::now()->subDays(25))
            ->state(fn (array $attributes) => [
                'situacao' => EmprestimoSituacao::Atrasado,
            ]);
    }

    public function vencidoEmAndamento(): static
    {
        return $this->retiradoEm(CarbonImmutable::now()->subDays(16));
    }

    public function venceAmanha(): static
    {
        return $this->retiradoEm(CarbonImmutable::now()->subDays(13));
    }

    public function encerradoPorBaixa(?CarbonImmutable $em = null): static
    {
        return $this->state(fn (array $attributes) => [
            'situacao' => EmprestimoSituacao::Encerrado,
            'encerrado_em' => $em ?? CarbonImmutable::now(),
        ]);
    }
}
