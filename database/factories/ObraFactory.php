<?php

namespace Database\Factories;

use Acesso\Domain\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Inventario\Domain\Models\Categoria;
use Inventario\Domain\Models\Editora;
use Inventario\Domain\Models\Obra;
use Inventario\Domain\ValueObjects\Isbn;

/**
 * @extends Factory<Obra>
 */
class ObraFactory extends Factory
{
    protected $model = Obra::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'titulo' => rtrim(fake()->unique()->sentence(3), '.'),
            'isbn' => Isbn::fromNative(self::isbn13()),
            'editora_id' => Editora::factory(),
            'categoria_id' => Categoria::factory(),
            'ano_publicacao' => fake()->numberBetween(1900, 2025),
            'capa_path' => null,
            'user_id' => User::factory()->bibliotecario(),
        ];
    }

    public function semIsbn(): static
    {
        return $this->state(fn (array $attributes) => [
            'isbn' => null,
        ]);
    }

    private static function isbn13(): string
    {
        $prefixo = '978'.fake()->unique()->numerify('#########');

        $soma = 0;
        for ($i = 0; $i < 12; $i++) {
            $soma += (int) $prefixo[$i] * ($i % 2 === 0 ? 1 : 3);
        }

        return $prefixo.((10 - ($soma % 10)) % 10);
    }
}
