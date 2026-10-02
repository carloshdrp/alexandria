<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Inventario\Domain\Models\Categoria;

/**
 * @extends Factory<Categoria>
 */
class CategoriaFactory extends Factory
{
    protected $model = Categoria::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nome' => ucfirst(fake()->unique()->word()),
            'user_id' => User::factory()->bibliotecario(),
        ];
    }
}
