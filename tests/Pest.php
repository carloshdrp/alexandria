<?php

use App\Acesso\Domain\Enums\UsuarioPapel;
use App\Acesso\Domain\Enums\UsuarioSituacao;
use App\Acesso\Domain\ValueObjects\Documento;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature', '../app/Acesso/Tests/Feature', '../src/Inventario/Tests/Feature', '../src/Emprestimos/Tests/Feature');

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function usuario(string $apelido, UsuarioPapel $papel = UsuarioPapel::Cliente): User
{
    static $sequencia = 0;

    $sequencia++;

    return User::forceCreate([
        'name' => $apelido,
        'email' => "{$apelido}-{$sequencia}@x.test",
        'email_verified_at' => CarbonImmutable::now(),
        'password' => 'x',
        'documento' => Documento::fromNative(str_pad((string) $sequencia, 11, '0', STR_PAD_LEFT)),
        'situacao' => UsuarioSituacao::Ativo,
        'papel' => $papel,
    ]);
}
