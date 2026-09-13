<?php

namespace Emprestimos\Domain\Services;

use App\Models\User;
use Carbon\CarbonImmutable;
use DomainException;
use Emprestimos\Domain\Models\Emprestimo;
use Emprestimos\Domain\Models\Multa;
use Emprestimos\Domain\ValueObjects\PrazoEmprestimo;
use Illuminate\Support\Facades\DB;
use Inventario\Domain\Models\Exemplar;

class RealizacaoEmprestimoService
{
    public function __construct() {}

    public function realizar(User $user, Exemplar $exemplar): Emprestimo
    {
        if (Multa::pendentePorUsuario($user->id)->exists()) {
            throw new DomainException('Usuário possui multa pendente');
        }

        if (Emprestimo::ativosPorUsuario($user->id)->count() >= Emprestimo::MAX_ATIVOS_POR_USUARIO) {
            throw new DomainException('Usuário já atingiu o limite de empréstimos ativos');
        }

        return DB::transaction(function () use ($user, $exemplar) {
            $exemplar->emprestar();
            $exemplar->save();

            $prazo = PrazoEmprestimo::iniciar(CarbonImmutable::now());

            return Emprestimo::create([
                'user_id' => $user->id,
                'exemplar_id' => $exemplar->id,
                'retirado_em' => $prazo->retiradoEm(),
                'prazo_devolucao' => $prazo->prazoDevolucao(),
            ]);
        });
    }
}
