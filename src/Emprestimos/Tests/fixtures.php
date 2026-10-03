<?php

use Acesso\Domain\Models\User;
use Carbon\CarbonImmutable;
use Emprestimos\Domain\Enums\EmprestimoSituacao;
use Emprestimos\Domain\Enums\ReservaSituacao;
use Emprestimos\Domain\Models\Emprestimo;
use Emprestimos\Domain\Models\Multa;
use Emprestimos\Domain\Models\Reserva;
use Emprestimos\Domain\Services\RealizacaoEmprestimoService;
use Emprestimos\Domain\ValueObjects\Dinheiro;
use Emprestimos\Domain\ValueObjects\JanelaReserva;
use Emprestimos\Domain\ValueObjects\PrazoEmprestimo;
use Inventario\Domain\Enums\ExemplarSituacao;
use Inventario\Domain\Models\Exemplar;

function emprestimoEmAndamento(User $user, Exemplar $exemplar, ?CarbonImmutable $retiradoEm = null): Emprestimo
{
    $emprestimo = new Emprestimo;
    $emprestimo->user_id = $user->id;
    $emprestimo->exemplar_id = $exemplar->id;
    $emprestimo->prazo = PrazoEmprestimo::iniciar($retiradoEm ?? CarbonImmutable::now()->subDays(3));
    $emprestimo->save();

    return $emprestimo;
}

function emprestimoDevolvido(User $user, Exemplar $exemplar, CarbonImmutable $retiradoEm, CarbonImmutable $devolvidoEm): Emprestimo
{
    $emprestimo = emprestimoEmAndamento($user, $exemplar, $retiradoEm);
    $emprestimo->situacao = EmprestimoSituacao::Devolvido;
    $emprestimo->devolvido_em = $devolvidoEm;
    $emprestimo->save();

    return $emprestimo;
}

function multaPendente(User $user): Multa
{
    $emprestimo = emprestimoDevolvido(
        $user,
        acervo(ExemplarSituacao::NoAcervo),
        CarbonImmutable::now()->subDays(15),
        CarbonImmutable::now(),
    );

    return Multa::create([
        'emprestimo_id' => $emprestimo->id,
        'user_id' => $user->id,
        'valor' => Dinheiro::fromNative(1.0),
        'dias_atraso' => 1,
    ]);
}

function reservaAguardando(User $user, Exemplar $exemplar, ?CarbonImmutable $criadaEm = null): Reserva
{
    $atributos = [
        'user_id' => $user->id,
        'obra_id' => $exemplar->obra_id,
        'enfileirada_em' => $criadaEm ?? CarbonImmutable::now(),
    ];

    if ($criadaEm !== null) {
        $atributos['created_at'] = $criadaEm;
        $atributos['updated_at'] = $criadaEm;
    }

    return Reserva::forceCreate($atributos);
}

function reservaDisponivel(User $user, Exemplar $exemplar, CarbonImmutable $disponibilizadaEm): Reserva
{
    $reserva = reservaAguardando($user, $exemplar);
    $reserva->exemplar_id = $exemplar->id;
    $reserva->janela = JanelaReserva::abrir($disponibilizadaEm);
    $reserva->situacao = ReservaSituacao::Disponivel;
    $reserva->save();

    return $reserva;
}

function realizarEmprestimo(User $user, Exemplar $exemplar): Emprestimo
{
    return app(RealizacaoEmprestimoService::class)->realizar($user, exemplarPublicado($exemplar));
}
