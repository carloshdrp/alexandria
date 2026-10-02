<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Inventario\Domain\Models\Autor;

/**
 * @extends Factory<Autor>
 */
class AutorFactory extends Factory
{
    protected $model = Autor::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nome' => fake()->unique()->name(),
            'user_id' => User::factory()->bibliotecario(),
        ];
    }
}
