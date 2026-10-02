<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Inventario\Domain\Models\Editora;

/**
 * @extends Factory<Editora>
 */
class EditoraFactory extends Factory
{
    protected $model = Editora::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nome' => 'Editora '.fake()->unique()->company(),
            'user_id' => User::factory()->bibliotecario(),
        ];
    }
}
