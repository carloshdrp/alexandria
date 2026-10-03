<?php

use Acesso\Domain\Enums\UsuarioPapel;
use Carbon\CarbonImmutable;
use Emprestimos\Application\Livewire\MinhasReservas;
use Emprestimos\Application\Livewire\ReservasAtivas;
use Emprestimos\Application\Notifications\ReservaCancelada;
use Emprestimos\Application\Notifications\ReservaDisponivel;
use Emprestimos\Application\Notifications\ReservaExpirada;
use Emprestimos\Domain\Enums\ReservaSituacao;
use Emprestimos\Domain\Services\CancelamentoReservaService;
use Emprestimos\Domain\Services\DevolucaoEmprestimoService;
use Emprestimos\Domain\Services\ExpiracaoReservaService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;
use Inventario\Domain\Enums\ExemplarSituacao;
use Livewire\Livewire;

it('promove sempre a reserva mais antiga, seja por devolução, expiração ou cancelamento', function () {
    Notification::fake();
    $inicio = CarbonImmutable::parse('2026-03-01 10:00:00');
    $this->travelTo($inicio);

    $exemplar = acervo(ExemplarSituacao::Emprestado);
    $emprestimo = emprestimoEmAndamento(usuario('leitor'), $exemplar, $inicio);

    $ana = usuario('ana');
    $bob = usuario('bob');
    $carla = usuario('carla');

    $daCarla = reservaAguardando($carla, $exemplar, $inicio->addMinutes(2));
    $doBob = reservaAguardando($bob, $exemplar, $inicio->addMinute());
    $daAna = reservaAguardando($ana, $exemplar, $inicio);

    $this->travelTo($inicio->addDay());

    app(DevolucaoEmprestimoService::class)->devolver($emprestimo);

    expect($daAna->refresh()->situacao)->toBe(ReservaSituacao::Disponivel)
        ->and($daAna->exemplar_id)->toBe($exemplar->id)
        ->and($doBob->refresh()->situacao)->toBe(ReservaSituacao::Aguardando)
        ->and($daCarla->refresh()->situacao)->toBe(ReservaSituacao::Aguardando)
        ->and($exemplar->refresh()->situacao)->toBe(ExemplarSituacao::Reservado);

    Notification::assertSentTo($ana, ReservaDisponivel::class);
    Notification::assertNotSentTo($bob, ReservaDisponivel::class);
    Notification::assertNotSentTo($carla, ReservaDisponivel::class);

    $this->travelTo($inicio->addDays(3)->addSecond());

    expect(app(ExpiracaoReservaService::class)->expirarVencidas())->toBe(1);

    expect($daAna->refresh()->situacao)->toBe(ReservaSituacao::Expirada)
        ->and($doBob->refresh()->situacao)->toBe(ReservaSituacao::Disponivel)
        ->and($doBob->exemplar_id)->toBe($exemplar->id)
        ->and($daCarla->refresh()->situacao)->toBe(ReservaSituacao::Aguardando)
        ->and($exemplar->refresh()->situacao)->toBe(ExemplarSituacao::Reservado);

    Notification::assertSentTo($ana, ReservaExpirada::class);
    Notification::assertSentTo($bob, ReservaDisponivel::class);
    Notification::assertNotSentTo($carla, ReservaDisponivel::class);

    app(CancelamentoReservaService::class)->cancelar($doBob);

    expect($doBob->refresh()->situacao)->toBe(ReservaSituacao::Cancelada)
        ->and($daCarla->refresh()->situacao)->toBe(ReservaSituacao::Disponivel)
        ->and($daCarla->exemplar_id)->toBe($exemplar->id)
        ->and($exemplar->refresh()->situacao)->toBe(ExemplarSituacao::Reservado);

    Notification::assertSentTo($bob, ReservaCancelada::class);
    Notification::assertSentTo($carla, ReservaDisponivel::class);
});

it('expira a reserva exatamente 48 horas depois de disponibilizada', function () {
    Notification::fake();
    $inicio = CarbonImmutable::parse('2026-03-01 10:00:00');
    $this->travelTo($inicio);

    $leitor = usuario('leitor');
    $exemplar = acervo(ExemplarSituacao::Reservado);
    $reserva = reservaDisponivel($leitor, $exemplar, $inicio);

    $this->travelTo($inicio->addHours(48)->subSecond());

    expect(app(ExpiracaoReservaService::class)->expirarVencidas())->toBe(0)
        ->and($reserva->refresh()->situacao)->toBe(ReservaSituacao::Disponivel);

    Notification::assertNothingSent();

    $this->travelTo($inicio->addHours(48));

    expect(app(ExpiracaoReservaService::class)->expirarVencidas())->toBe(1)
        ->and($reserva->refresh()->situacao)->toBe(ReservaSituacao::Expirada)
        ->and($exemplar->refresh()->situacao)->toBe(ExemplarSituacao::NoAcervo);

    Notification::assertSentTo($leitor, ReservaExpirada::class, function (ReservaExpirada $notificacao) use ($leitor, $exemplar) {
        return $notificacao->toMail($leitor)->subject === 'Reserva expirada: '.obraPublicada($exemplar)->titulo;
    });
});

it('expira pelo comando agendado', function () {
    Notification::fake();

    reservaDisponivel(usuario('leitor'), acervo(ExemplarSituacao::Reservado), CarbonImmutable::now()->subHours(49));

    $this->artisan('reservas:expirar')
        ->expectsOutputToContain('Reservas expiradas: 1')
        ->assertSuccessful();
});

it('o leitor cancela a própria reserva na fila', function () {
    Notification::fake();

    $leitor = usuario('leitor');
    $exemplar = acervo(ExemplarSituacao::Emprestado);
    $reserva = reservaAguardando($leitor, $exemplar);

    Livewire::actingAs($leitor)
        ->test(MinhasReservas::class)
        ->call('cancelar', $reserva->id)
        ->assertHasNoErrors();

    expect($reserva->refresh()->situacao)->toBe(ReservaSituacao::Cancelada)
        ->and($exemplar->refresh()->situacao)->toBe(ExemplarSituacao::Emprestado);

    Notification::assertSentTo($leitor, ReservaCancelada::class);
});

it('o leitor não cancela reserva de outro', function () {
    $leitor = usuario('leitor');
    $deOutro = reservaAguardando(usuario('outro'), acervo(ExemplarSituacao::Emprestado));

    Livewire::actingAs($leitor)
        ->test(MinhasReservas::class)
        ->call('cancelar', $deOutro->id)
        ->assertForbidden();

    expect($deOutro->refresh()->situacao)->toBe(ReservaSituacao::Aguardando);
});

it('não cancela reserva já atendida', function () {
    $leitor = usuario('leitor');
    $reserva = reservaDisponivel($leitor, acervo(ExemplarSituacao::Reservado), CarbonImmutable::now());
    $reserva->atender();
    $reserva->save();

    Livewire::actingAs($leitor)
        ->test(MinhasReservas::class)
        ->call('cancelar', $reserva->id)
        ->assertHasErrors('dominio');

    expect($reserva->refresh()->situacao)->toBe(ReservaSituacao::Atendida);
});

it('mostra a cada leitor sua posição na fila', function () {
    $inicio = CarbonImmutable::parse('2026-03-01 10:00:00');
    $exemplar = acervo(ExemplarSituacao::Emprestado);

    $ana = usuario('ana');
    $carla = usuario('carla');

    $daCarla = reservaAguardando($carla, $exemplar, $inicio->addMinutes(2));
    reservaAguardando(usuario('bob'), $exemplar, $inicio->addMinute());
    $daAna = reservaAguardando($ana, $exemplar, $inicio);

    Livewire::actingAs($carla)
        ->test(MinhasReservas::class)
        ->assertViewHas('posicoes', fn (Collection $posicoes) => $posicoes->get($daCarla->id) === 3);

    Livewire::actingAs($ana)
        ->test(MinhasReservas::class)
        ->assertViewHas('posicoes', fn (Collection $posicoes) => $posicoes->get($daAna->id) === 1);
});

it('o bibliotecário cancela pela lista de reservas ativas', function () {
    Notification::fake();

    $bibliotecario = usuario('bib', UsuarioPapel::Bibliotecario);
    $leitor = usuario('leitor');
    $reserva = reservaAguardando($leitor, acervo(ExemplarSituacao::Emprestado));

    Livewire::actingAs($bibliotecario)
        ->test(ReservasAtivas::class)
        ->call('cancelar', $reserva->id)
        ->assertHasNoErrors();

    expect($reserva->refresh()->situacao)->toBe(ReservaSituacao::Cancelada);

    Notification::assertSentTo($leitor, ReservaCancelada::class);
});
