<?php

namespace Database\Factories;

use App\Acesso\Domain\Enums\UsuarioPapel;
use App\Acesso\Domain\Enums\UsuarioSituacao;
use App\Models\User;
use App\Acesso\Domain\ValueObjects\Documento;
use App\Acesso\Domain\ValueObjects\Telefone;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'documento' => Documento::fromNative(fake()->unique()->numerify('###########')),
            'telefone' => Telefone::fromNative('119'.fake()->numerify('########')),
            'papel' => UsuarioPapel::Cliente,
            'situacao' => UsuarioSituacao::Ativo,
            'remember_token' => Str::random(10),
        ];
    }

    public function naoVerificado(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function bibliotecario(): static
    {
        return $this->state(fn (array $attributes) => [
            'papel' => UsuarioPapel::Bibliotecario,
        ]);
    }

    public function bloqueado(): static
    {
        return $this->state(fn (array $attributes) => [
            'situacao' => UsuarioSituacao::Bloqueado,
            'bloqueado_em' => now(),
        ]);
    }
}
