<?php

namespace Emprestimos\Domain\Services;

use App\Models\User;
use Carbon\CarbonImmutable;
use DomainException;
use Emprestimos\Domain\Events\EmprestimoFoiRealizado;
use Emprestimos\Domain\Models\Emprestimo;
use Emprestimos\Domain\Models\Multa;
use Emprestimos\Domain\ValueObjects\PrazoEmprestimo;
use Illuminate\Support\Facades\DB;
use Inventario\Domain\Services\AcervoService;
use Inventario\Domain\ValueObjects\ExemplarDoAcervo;

class RealizacaoEmprestimoService
{
    public function __construct(
        private readonly AcervoService $acervo,
    ) {}

    public function realizar(User $user, ExemplarDoAcervo $exemplar): Emprestimo
    {
        $this->garantirElegibilidade($user);

        $emprestimo = DB::transaction(function () use ($user, $exemplar) {
            $this->acervo->marcarEmprestado($exemplar->id);

            return $this->registrar($user->id, $exemplar->id);
        });

        event(new EmprestimoFoiRealizado($emprestimo));

        return $emprestimo;
    }

    public function garantirElegibilidade(User $user): void
    {
        if ($user->estaBloqueado()) {
            throw new DomainException('Usuário está bloqueado');
        }

        if (Multa::pendentePorUsuario($user->id)->exists()) {
            throw new DomainException('Usuário possui multa pendente');
        }

        if (Emprestimo::ativosPorUsuario($user->id)->count() >= Emprestimo::MAX_ATIVOS_POR_USUARIO) {
            throw new DomainException('Usuário já atingiu o limite de empréstimos ativos');
        }
    }

    public function registrar(int $userId, int $exemplarId): Emprestimo
    {
        $emprestimo = new Emprestimo([
            'user_id' => $userId,
            'exemplar_id' => $exemplarId,
        ]);

        $emprestimo->prazo = PrazoEmprestimo::iniciar(CarbonImmutable::now());
        $emprestimo->save();

        return $emprestimo;
    }
}
