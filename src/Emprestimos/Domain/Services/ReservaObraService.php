<?php

namespace Emprestimos\Domain\Services;

use Acesso\Domain\Models\User;
use Carbon\CarbonImmutable;
use DomainException;
use Emprestimos\Domain\Events\ReservaFoiCadastrada;
use Emprestimos\Domain\Models\Emprestimo;
use Emprestimos\Domain\Models\Reserva;
use Inventario\Domain\Services\AcervoService;
use Inventario\Domain\ValueObjects\ExemplarDoAcervo;
use Inventario\Domain\ValueObjects\ObraDoAcervo;

class ReservaObraService
{
    public function __construct(
        private readonly AcervoService $acervo,
    ) {}

    public function reservar(User $user, ObraDoAcervo $obra): Reserva
    {
        if ($user->estaBloqueado()) {
            throw new DomainException('Usuário está bloqueado');
        }

        if ($obra->exemplaresTotal === 0) {
            throw new DomainException('A obra não possui exemplares ativos no acervo.');
        }

        if ($this->acervo->existeExemplarDisponivel($obra->id)) {
            throw new DomainException('Já existem exemplares disponíveis para esta obra');
        }

        if (Reserva::reservaPorObra($obra->id)->where('user_id', $user->id)->exists()) {
            throw new DomainException('Já existe uma reserva ativa desta obra para este usuário');
        }

        if ($this->temExemplarEmprestado($user, $obra)) {
            throw new DomainException('Este usuário já está com um exemplar emprestado');
        }

        $reserva = Reserva::create([
            'user_id' => $user->id,
            'obra_id' => $obra->id,
            'enfileirada_em' => CarbonImmutable::now(),
        ]);

        event(new ReservaFoiCadastrada($reserva));

        return $reserva;
    }

    public function temExemplarEmprestado(User $user, ObraDoAcervo $obra): bool
    {
        $exemplarIds = Emprestimo::ativosPorUsuario($user->id)->pluck('exemplar_id')->all();

        if ($exemplarIds === []) {
            return false;
        }

        return $this->acervo->exemplares($exemplarIds)
            ->contains(fn (ExemplarDoAcervo $exemplar) => $exemplar->obraId === $obra->id);
    }
}
