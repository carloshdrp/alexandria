<?php

use App\Acesso\Domain\Enums\UsuarioPapel;
use App\Acesso\Application\Livewire\Clientes\EditarCliente;
use App\Acesso\Application\Livewire\Perfil\EditarPerfil;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

it('atualiza os dados do próprio perfil sem exigir nova confirmação', function () {
    $usuario = usuario('leitor');

    Livewire::actingAs($usuario)
        ->test(EditarPerfil::class)
        ->set('name', 'Nome Novo')
        ->set('telefone', '(11) 98888-7777')
        ->call('salvar')
        ->assertHasNoErrors();

    $usuario->refresh();

    expect($usuario->name)->toBe('Nome Novo')
        ->and($usuario->telefone->getNativeValue())->toBe('11988887777')
        ->and($usuario->hasVerifiedEmail())->toBeTrue();
});

it('grava o CPF com máscara apenas como dígitos', function () {
    $usuario = usuario('leitor');

    Livewire::actingAs($usuario)
        ->test(EditarPerfil::class)
        ->set('documento', '529.982.247-25')
        ->call('salvar')
        ->assertHasNoErrors();

    expect($usuario->refresh()->documento->getNativeValue())->toBe('52998224725');
});

it('exige nova confirmação quando o e-mail muda', function () {
    Notification::fake();

    $usuario = usuario('leitor');

    Livewire::actingAs($usuario)
        ->test(EditarPerfil::class)
        ->set('email', 'outro@alexandria.test')
        ->call('salvar')
        ->assertHasNoErrors();

    $usuario->refresh();

    expect($usuario->email)->toBe('outro@alexandria.test')
        ->and($usuario->hasVerifiedEmail())->toBeFalse();

    Notification::assertSentTo($usuario, VerifyEmail::class);
});

it('recusa a troca de senha quando a senha atual está errada', function () {
    $usuario = usuario('leitor');
    $usuario->forceFill(['password' => Hash::make('senha-atual-123')])->save();

    Livewire::actingAs($usuario)
        ->test(EditarPerfil::class)
        ->set('current_password', 'senha-errada')
        ->set('password', 'senha-nova-456')
        ->set('password_confirmation', 'senha-nova-456')
        ->call('alterarSenha')
        ->assertHasErrors('current_password');

    expect(Hash::check('senha-atual-123', $usuario->refresh()->password))->toBeTrue();
});

it('altera a senha com a senha atual correta', function () {
    $usuario = usuario('leitor');
    $usuario->forceFill(['password' => Hash::make('senha-atual-123')])->save();

    Livewire::actingAs($usuario)
        ->test(EditarPerfil::class)
        ->set('current_password', 'senha-atual-123')
        ->set('password', 'senha-nova-456')
        ->set('password_confirmation', 'senha-nova-456')
        ->call('alterarSenha')
        ->assertHasNoErrors();

    expect(Hash::check('senha-nova-456', $usuario->refresh()->password))->toBeTrue();
});

it('deixa o bibliotecário editar o cadastro de um cliente', function () {
    $bibliotecario = usuario('bibliotecario', UsuarioPapel::Bibliotecario);
    $cliente = usuario('cliente');

    Livewire::actingAs($bibliotecario)
        ->test(EditarCliente::class, ['user' => $cliente])
        ->set('name', 'Cliente Corrigido')
        ->call('salvar')
        ->assertHasNoErrors();

    expect($cliente->refresh()->name)->toBe('Cliente Corrigido');
});

it('recusa editar um bibliotecário pela tela de cliente', function () {
    $bibliotecario = usuario('bibliotecario', UsuarioPapel::Bibliotecario);
    $outro = usuario('outro', UsuarioPapel::Bibliotecario);

    Livewire::actingAs($bibliotecario)
        ->test(EditarCliente::class, ['user' => $outro])
        ->assertStatus(404);
});

it('envia link de recuperação de senha para o cliente', function () {
    Notification::fake();

    $bibliotecario = usuario('bibliotecario', UsuarioPapel::Bibliotecario);
    $cliente = usuario('cliente');

    Livewire::actingAs($bibliotecario)
        ->test(EditarCliente::class, ['user' => $cliente])
        ->call('enviarLinkDeSenha')
        ->assertHasNoErrors();

    Notification::assertSentTo($cliente, ResetPassword::class);
});

it('recusa CPF já usado por outro usuário', function () {
    $usuario = usuario('leitor');
    $outro = usuario('outro');

    Livewire::actingAs($usuario)
        ->test(EditarPerfil::class)
        ->set('documento', $outro->documento->formatado())
        ->call('salvar')
        ->assertHasErrors('documento');
});

it('revoga a sessão do usuário bloqueado', function () {
    $cliente = usuario('cliente');

    $this->actingAs($cliente)->get(route('emprestimos.catalogo'))->assertOk();

    $cliente->bloquear();
    $cliente->save();

    $this->actingAs($cliente)
        ->get(route('emprestimos.catalogo'))
        ->assertRedirect(route('login', ['bloqueado' => 1]));
});

it('barra o acesso de quem não confirmou o e-mail', function () {
    $cliente = User::factory()->naoVerificado()->create();

    $this->actingAs($cliente)
        ->get(route('emprestimos.catalogo'))
        ->assertRedirect(route('verification.notice'));
});
