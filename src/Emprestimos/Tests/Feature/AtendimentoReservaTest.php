<?php

use App\Acesso\Domain\Enums\UsuarioPapel;
use Carbon\CarbonImmutable;
use Emprestimos\Application\Livewire\ReservasAtivas;
use Emprestimos\Application\Livewire\SituacaoDoCliente;
use Emprestimos\Application\Notifications\EmprestimoRealizado;
use Emprestimos\Domain\Enums\ReservaSituacao;
use Emprestimos\Domain\Events\ReservaFoiAtendida;
use Emprestimos\Domain\Models\Emprestimo;
use Emprestimos\Domain\Services\AtendimentoReservaService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Inventario\Domain\Enums\ExemplarSituacao;
use Livewire\Livewire;

it('entrega o exemplar reservado e abre o empréstimo do dono da reserva', function () {
    Event::fake([ReservaFoiAtendida::class]);
    Notification::fake();
    $this->travelTo(CarbonImmutable::parse('2026-03-01 10:00:00'));

    $leitor = usuario('leitor');
    $exemplar = acervo(ExemplarSituacao::Reservado);
    $reserva = reservaDisponivel($leitor, $exemplar, CarbonImmutable::now());

    $this->travelTo(CarbonImmutable::parse('2026-03-02 10:00:00'));

    $emprestimo = app(AtendimentoReservaService::class)->atender($reserva);

    expect($reserva->refresh()->situacao)->toBe(ReservaSituacao::Atendida)
        ->and($exemplar->refresh()->situacao)->toBe(ExemplarSituacao::Emprestado)
        ->and($emprestimo->refresh()->user_id)->toBe($leitor->id)
        ->and($emprestimo->exemplar_id)->toBe($exemplar->id)
        ->and($emprestimo->prazo->prazoDevolucao()->format('Y-m-d'))->toBe('2026-03-16')
        ->and(Emprestimo::ativosPorUsuario($leitor->id)->count())->toBe(1);

    Event::assertDispatched(ReservaFoiAtendida::class, fn (ReservaFoiAtendida $evento) => $evento->reserva->is($reserva));
    Notification::assertSentTo($leitor, EmprestimoRealizado::class);
});

it('atende até o último segundo da janela de 48 horas', function () {
    $inicio = CarbonImmutable::parse('2026-03-01 10:00:00');
    $this->travelTo($inicio);

    $reserva = reservaDisponivel(usuario('leitor'), acervo(ExemplarSituacao::Reservado), $inicio);

    $this->travelTo($inicio->addHours(48));

    app(AtendimentoReservaService::class)->atender($reserva);

    expect($reserva->refresh()->situacao)->toBe(ReservaSituacao::Atendida);
});

it('recusa a retirada um segundo depois da janela', function () {
    $inicio = CarbonImmutable::parse('2026-03-01 10:00:00');
    $this->travelTo($inicio);

    $exemplar = acervo(ExemplarSituacao::Reservado);
    $reserva = reservaDisponivel(usuario('leitor'), $exemplar, $inicio);

    $this->travelTo($inicio->addHours(48)->addSecond());

    expect(fn () => app(AtendimentoReservaService::class)->atender($reserva))
        ->toThrow(DomainException::class, 'janela de retirada');

    expect($reserva->refresh()->situacao)->toBe(ReservaSituacao::Disponivel)
        ->and($exemplar->refresh()->situacao)->toBe(ExemplarSituacao::Reservado)
        ->and(Emprestimo::count())->toBe(0);
});

it('recusa atender reserva que ainda aguarda na fila', function () {
    $exemplar = acervo(ExemplarSituacao::Emprestado);
    $reserva = reservaAguardando(usuario('leitor'), $exemplar);

    expect(fn () => app(AtendimentoReservaService::class)->atender($reserva))
        ->toThrow(DomainException::class);

    expect($reserva->refresh()->situacao)->toBe(ReservaSituacao::Aguardando)
        ->and(Emprestimo::count())->toBe(0);
});

it('recusa a retirada quando o dono da reserva tem multa pendente', function () {
    $leitor = usuario('leitor');
    $exemplar = acervo(ExemplarSituacao::Reservado);
    $reserva = reservaDisponivel($leitor, $exemplar, CarbonImmutable::now());

    multaPendente($leitor);

    expect(fn () => app(AtendimentoReservaService::class)->atender($reserva))
        ->toThrow(DomainException::class, 'multa pendente');

    expect($reserva->refresh()->situacao)->toBe(ReservaSituacao::Disponivel)
        ->and($exemplar->refresh()->situacao)->toBe(ExemplarSituacao::Reservado)
        ->and(Emprestimo::ativosPorUsuario($leitor->id)->count())->toBe(0);
});

it('recusa a retirada quando o dono da reserva está bloqueado', function () {
    $leitor = usuario('leitor');
    $exemplar = acervo(ExemplarSituacao::Reservado);
    $reserva = reservaDisponivel($leitor, $exemplar, CarbonImmutable::now());

    $leitor->bloquear();
    $leitor->save();

    expect(fn () => app(AtendimentoReservaService::class)->atender($reserva))
        ->toThrow(DomainException::class, 'bloqueado');

    expect($reserva->refresh()->situacao)->toBe(ReservaSituacao::Disponivel)
        ->and($exemplar->refresh()->situacao)->toBe(ExemplarSituacao::Reservado);
});

it('recusa a retirada quando o dono da reserva já tem três empréstimos ativos', function () {
    $leitor = usuario('leitor');
    $exemplar = acervo(ExemplarSituacao::Reservado);
    $reserva = reservaDisponivel($leitor, $exemplar, CarbonImmutable::now());

    foreach (range(1, 3) as $vez) {
        emprestimoEmAndamento($leitor, acervo(ExemplarSituacao::Emprestado));
    }

    expect(fn () => app(AtendimentoReservaService::class)->atender($reserva))
        ->toThrow(DomainException::class, 'limite de empréstimos ativos');

    expect($reserva->refresh()->situacao)->toBe(ReservaSituacao::Disponivel)
        ->and($exemplar->refresh()->situacao)->toBe(ExemplarSituacao::Reservado)
        ->and(Emprestimo::ativosPorUsuario($leitor->id)->count())->toBe(3);
});

it('só o bibliotecário atende reservas', function () {
    $leitor = usuario('leitor');
    $reserva = reservaDisponivel($leitor, acervo(ExemplarSituacao::Reservado), CarbonImmutable::now());

    Livewire::actingAs($leitor)
        ->test(ReservasAtivas::class)
        ->call('atender', $reserva->id)
        ->assertForbidden();

    expect($reserva->refresh()->situacao)->toBe(ReservaSituacao::Disponivel)
        ->and(Emprestimo::count())->toBe(0);
});

it('o bibliotecário atende pela lista de reservas ativas', function () {
    $bibliotecario = usuario('bib', UsuarioPapel::Bibliotecario);
    $leitor = usuario('leitor');
    $exemplar = acervo(ExemplarSituacao::Reservado);
    $reserva = reservaDisponivel($leitor, $exemplar, CarbonImmutable::now());

    Livewire::actingAs($bibliotecario)
        ->test(ReservasAtivas::class)
        ->call('atender', $reserva->id)
        ->assertHasNoErrors();

    expect($reserva->refresh()->situacao)->toBe(ReservaSituacao::Atendida)
        ->and(Emprestimo::where('user_id', $leitor->id)->where('exemplar_id', $exemplar->id)->exists())->toBeTrue();
});

it('a ficha do cliente só atende reservas daquele cliente', function () {
    $bibliotecario = usuario('bib', UsuarioPapel::Bibliotecario);
    $cliente = usuario('cliente');
    $deOutro = reservaDisponivel(usuario('outro'), acervo(ExemplarSituacao::Reservado), CarbonImmutable::now());

    expect(fn () => Livewire::actingAs($bibliotecario)
        ->test(SituacaoDoCliente::class, ['user' => $cliente])
        ->call('atenderReserva', $deOutro->id))
        ->toThrow(ModelNotFoundException::class);

    expect($deOutro->refresh()->situacao)->toBe(ReservaSituacao::Disponivel);
});
