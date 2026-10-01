<?php

namespace Emprestimos\Domain\Services;

use Carbon\CarbonImmutable;
use DomainException;
use Emprestimos\Domain\Events\EmprestimoFoiRenovado;
use Emprestimos\Domain\Models\Emprestimo;
use Emprestimos\Domain\Models\EmprestimoRenovacao;
use Emprestimos\Domain\Models\Multa;
use Emprestimos\Domain\Models\Reserva;
use Illuminate\Support\Facades\DB;
use Inventario\Domain\Services\AcervoService;

class RenovacaoEmprestimoService
{
    public function __construct(
        private readonly AcervoService $acervo,
    ) {}

    public function renovar(Emprestimo $emprestimo): void
    {
        $exemplar = $this->acervo->exemplar($emprestimo->exemplar_id);

        if ($exemplar === null) {
            throw new DomainException('Exemplar do empréstimo não existe no acervo.');
        }

        $temMultaPendente = Multa::pendentePorUsuario($emprestimo->user_id)->exists();

        $temReservaPendente = Reserva::reservaPorObra(
            $exemplar->obraId,
            $emprestimo->user_id
        )->exists();

        DB::transaction(function () use ($emprestimo, $temReservaPendente, $temMultaPendente) {
            $prazoAnterior = $emprestimo->prazo->prazoDevolucao();

            $emprestimo->renovar($temReservaPendente, $temMultaPendente);
            $emprestimo->save();

            EmprestimoRenovacao::create([
                'emprestimo_id' => $emprestimo->id,
                'user_id' => $emprestimo->user_id,
                'sequencia' => $emprestimo->qtd_renovacoes,
                'prazo_anterior' => $prazoAnterior,
                'prazo_novo' => $emprestimo->prazo->prazoDevolucao(),
                'renovado_em' => CarbonImmutable::now(),
            ]);
        });

        event(new EmprestimoFoiRenovado($emprestimo));
    }
}
