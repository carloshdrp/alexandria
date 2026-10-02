<?php

use App\Acesso\Domain\Enums\UsuarioPapel;
use Carbon\CarbonImmutable;
use Emprestimos\Application\Listeners\AjustarFilaDeReservaAposBaixa;
use Emprestimos\Application\Listeners\EncerrarEmprestimoDoExemplarBaixado;
use Emprestimos\Application\Notifications\EmprestimoEncerrado;
use Emprestimos\Application\Notifications\ReservaCanceladaPorIndisponibilidade;
use Emprestimos\Application\Notifications\ReservaReenfileirada;
use Emprestimos\Domain\Enums\EmprestimoSituacao;
use Emprestimos\Domain\Enums\ReservaSituacao;
use Emprestimos\Domain\Models\Multa;
use Illuminate\Events\CallQueuedListener;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Inventario\Application\Livewire\ExemplaresDaObra;
use Inventario\Domain\Enums\ExemplarMotivoBaixa;
use Inventario\Domain\Enums\ExemplarSituacao;
use Inventario\Domain\Events\Integracao\ExemplarFoiBaixado;
use Inventario\Domain\Models\Exemplar;
use Inventario\Domain\Models\Obra;
use Livewire\Livewire;

function baixar(Exemplar $exemplar, ExemplarMotivoBaixa $motivo = ExemplarMotivoBaixa::Perdido): void
{
    Livewire::actingAs(usuario('bib', UsuarioPapel::Bibliotecario))
        ->test(ExemplaresDaObra::class, ['obra' => Obra::findOrFail($exemplar->obra_id)])
        ->call('baixar', $exemplar->id, $motivo->value)
        ->assertHasNoErrors();
}

it('o evento de integração trafega só os ids, sem entidade do Inventário', function () {
    $exemplar = acervo(ExemplarSituacao::NoAcervo);

    $evento = new ExemplarFoiBaixado($exemplar->id, $exemplar->obra_id);

    expect($evento->entityKey())->toBe($exemplar->id)
        ->and(get_object_vars($evento))->toBe([
            'exemplarId' => $exemplar->id,
            'obraId' => $exemplar->obra_id,
        ]);
});

it('encerra o empréstimo em curso quando o exemplar é baixado', function () {
    Notification::fake();
    $this->travelTo(CarbonImmutable::parse('2026-03-10 09:00:00'));

    $leitor = usuario('leitor');
    $exemplar = acervo(ExemplarSituacao::NoAcervo);
    $emprestimo = realizarEmprestimo($leitor, $exemplar);

    baixar($exemplar);

    $emprestimo->refresh();

    expect($emprestimo->situacao)->toBe(EmprestimoSituacao::Encerrado)
        ->and($emprestimo->encerrado_em?->format('Y-m-d H:i'))->toBe('2026-03-10 09:00')
        ->and($emprestimo->devolvido_em)->toBeNull()
        ->and(Multa::where('emprestimo_id', $emprestimo->id)->count())->toBe(0);

    Notification::assertSentTo($leitor, EmprestimoEncerrado::class);
});

it('cancela a fila inteira quando a obra fica sem exemplar ativo', function () {
    Notification::fake();

    $exemplar = acervo(ExemplarSituacao::NoAcervo);
    $primeiro = usuario('primeiro');
    $segundo = usuario('segundo');

    $disponivel = reservaDisponivel($primeiro, $exemplar, CarbonImmutable::now());
    $aguardando = reservaAguardando($segundo, $exemplar);

    $exemplar->refresh();

    baixar($exemplar);

    expect($disponivel->refresh()->situacao)->toBe(ReservaSituacao::Cancelada)
        ->and($aguardando->refresh()->situacao)->toBe(ReservaSituacao::Cancelada);

    Notification::assertSentTo($primeiro, ReservaCanceladaPorIndisponibilidade::class);
    Notification::assertSentTo($segundo, ReservaCanceladaPorIndisponibilidade::class);
});

it('reenfileira a reserva presa ao exemplar baixado quando a obra ainda tem acervo', function () {
    Notification::fake();

    $baixado = acervo(ExemplarSituacao::NoAcervo);
    $sobrevivente = exemplarIrmao($baixado, ExemplarSituacao::NoAcervo);

    $leitor = usuario('leitor');
    $reserva = reservaDisponivel($leitor, $baixado, CarbonImmutable::now());

    $baixado->refresh();

    baixar($baixado, ExemplarMotivoBaixa::Danificado);

    $reserva->refresh();

    expect($reserva->situacao)->toBe(ReservaSituacao::Aguardando)
        ->and($reserva->exemplar_id)->toBeNull()
        ->and($sobrevivente->refresh()->situacao)->toBe(ExemplarSituacao::NoAcervo);

    Notification::assertSentTo($leitor, ReservaReenfileirada::class);
});

it('as duas consequências da baixa correm na fila, não no request', function () {
    Queue::fake();

    $exemplar = acervo(ExemplarSituacao::NoAcervo);

    baixar($exemplar);

    Queue::assertPushed(
        CallQueuedListener::class,
        fn (CallQueuedListener $job) => $job->class === EncerrarEmprestimoDoExemplarBaixado::class,
    );

    Queue::assertPushed(
        CallQueuedListener::class,
        fn (CallQueuedListener $job) => $job->class === AjustarFilaDeReservaAposBaixa::class,
    );
});
