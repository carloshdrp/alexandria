<?php

use App\Acesso\Domain\Enums\UsuarioPapel;
use App\Acesso\Domain\Enums\UsuarioSituacao;
use App\Acesso\Application\Livewire\Clientes\CadastroCliente;
use App\Models\User;
use App\Acesso\Domain\ValueObjects\Documento;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

function cadastroValido(array $sobrescrever = []): array
{
    return array_merge([
        'name' => 'Novo Leitor',
        'email' => 'novo@alexandria.test',
        'documento' => '529.982.247-25',
        'telefone' => '(11) 98888-7777',
        'password' => 'senha-forte-123',
        'password_confirmation' => 'senha-forte-123',
    ], $sobrescrever);
}

it('registra um leitor pelo formulário público', function () {
    Notification::fake();

    $this->post('/register', cadastroValido())->assertRedirect();

    $leitor = User::where('email', 'novo@alexandria.test')->first();

    expect($leitor)->not->toBeNull()
        ->and($leitor->papel)->toBe(UsuarioPapel::Cliente)
        ->and($leitor->situacao)->toBe(UsuarioSituacao::Ativo)
        ->and($leitor->hasVerifiedEmail())->toBeFalse()
        ->and($leitor->documento->getNativeValue())->toBe('52998224725')
        ->and($leitor->telefone?->getNativeValue())->toBe('11988887777');

    $this->assertAuthenticatedAs($leitor);

    Notification::assertSentTo($leitor, VerifyEmail::class);
});

it('recusa cadastro inválido', function (array $campos, string $erro) {
    User::factory()->create(['email' => 'ja@alexandria.test', 'documento' => Documento::fromNative('11122233344')]);

    $this->from('/register')
        ->post('/register', cadastroValido($campos))
        ->assertRedirect('/register')
        ->assertSessionHasErrors($erro);

    expect(User::count())->toBe(1);

    $this->assertGuest();
})->with([
    'CPF já usado, enviado com máscara' => [['documento' => '111.222.333-44'], 'documento'],
    'CPF com dez dígitos' => [['documento' => '123.456.789-0'], 'documento'],
    'telefone com DDD zero' => [['telefone' => '(01) 98888-7777'], 'telefone'],
    'senha curta' => [['password' => 'curta', 'password_confirmation' => 'curta'], 'password'],
    'senha sem confirmação' => [['password_confirmation' => 'outra-coisa-123'], 'password'],
    'e-mail já usado' => [['email' => 'ja@alexandria.test'], 'email'],
    'e-mail inválido' => [['email' => 'nao-e-email'], 'email'],
]);

it('nunca registra bibliotecário pelo formulário público', function () {
    Notification::fake();

    $this->post('/register', cadastroValido([
        'papel' => UsuarioPapel::Bibliotecario->value,
        'situacao' => UsuarioSituacao::Bloqueado->value,
    ]));

    $leitor = User::where('email', 'novo@alexandria.test')->firstOrFail();

    expect($leitor->papel)->toBe(UsuarioPapel::Cliente)
        ->and($leitor->situacao)->toBe(UsuarioSituacao::Ativo)
        ->and($leitor->ehBibliotecario())->toBeFalse();
});

it('cadastra um cliente no balcão já com e-mail confirmado', function () {
    Notification::fake();

    $bibliotecario = usuario('bib', UsuarioPapel::Bibliotecario);

    $componente = Livewire::actingAs($bibliotecario)
        ->test(CadastroCliente::class)
        ->set('name', 'Cliente do Balcão')
        ->set('email', 'balcao@alexandria.test')
        ->set('documento', '529.982.247-25')
        ->set('telefone', '')
        ->set('password', 'senha-forte-123')
        ->set('password_confirmation', 'senha-forte-123')
        ->call('cadastrar')
        ->assertHasNoErrors();

    $cliente = User::where('email', 'balcao@alexandria.test')->firstOrFail();

    $componente->assertRedirect(route('emprestimos.clientes.situacao', $cliente));

    expect($cliente->hasVerifiedEmail())->toBeTrue()
        ->and($cliente->papel)->toBe(UsuarioPapel::Cliente)
        ->and($cliente->telefone)->toBeNull()
        ->and($cliente->documento->getNativeValue())->toBe('52998224725');

    Notification::assertNotSentTo($cliente, VerifyEmail::class);
});

it('mostra no balcão os erros do cadastro', function () {
    $bibliotecario = usuario('bib', UsuarioPapel::Bibliotecario);

    Livewire::actingAs($bibliotecario)
        ->test(CadastroCliente::class)
        ->set('name', 'Cliente')
        ->set('email', 'x@alexandria.test')
        ->set('documento', '123')
        ->set('password', 'senha-forte-123')
        ->set('password_confirmation', 'senha-forte-123')
        ->call('cadastrar')
        ->assertHasErrors('documento');

    expect(User::where('email', 'x@alexandria.test')->exists())->toBeFalse();
});

it('só o bibliotecário cadastra clientes no balcão', function () {
    $this->actingAs(usuario('leitor'))
        ->get(route('clientes.novo'))
        ->assertForbidden();
});

it('autentica com as credenciais certas', function () {
    $leitor = User::factory()->create(['email' => 'leitor@alexandria.test']);

    $this->post('/login', ['email' => 'leitor@alexandria.test', 'password' => 'password'])->assertRedirect();

    $this->assertAuthenticatedAs($leitor);
});

it('recusa a senha errada', function () {
    User::factory()->create(['email' => 'leitor@alexandria.test']);

    $this->from('/login')
        ->post('/login', ['email' => 'leitor@alexandria.test', 'password' => 'errada-123'])
        ->assertRedirect('/login')
        ->assertSessionHasErrors('email');

    $this->assertGuest();
});

it('recusa o login de usuário bloqueado', function () {
    User::factory()->create(['email' => 'bloqueado@alexandria.test', 'situacao' => UsuarioSituacao::Bloqueado]);

    $this->from('/login')
        ->post('/login', ['email' => 'bloqueado@alexandria.test', 'password' => 'password'])
        ->assertRedirect('/login')
        ->assertSessionHasErrors('email');

    $this->assertGuest();
});

it('encerra a sessão no logout', function () {
    $leitor = usuario('leitor');

    $this->actingAs($leitor)->post('/logout')->assertRedirect();

    $this->assertGuest();
});

it('exige login para o catálogo', function () {
    $this->get(route('emprestimos.catalogo'))->assertRedirect('/login');
});
