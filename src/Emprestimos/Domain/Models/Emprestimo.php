<?php

namespace Emprestimos\Domain\Models;

use App\Models\User;
use Carbon\CarbonImmutable;
use DateTimeInterface;
use DomainException;
use Emprestimos\Domain\Enums\EmprestimoSituacao;
use Emprestimos\Domain\Observers\EmprestimoObserver;
use Emprestimos\Infrastructure\Casts\PrazoEmprestimoCast;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[ObservedBy([EmprestimoObserver::class])]
#[Fillable(['user_id', 'exemplar_id'])]
class Emprestimo extends Model
{
    public const int MAX_RENOVACOES = 2;

    public const int MAX_ATIVOS_POR_USUARIO = 3;

    protected $attributes = [
        'situacao' => EmprestimoSituacao::Andamento->value,
        'qtd_renovacoes' => 0,
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function renovacoes(): HasMany
    {
        return $this->hasMany(EmprestimoRenovacao::class);
    }

    public function multa(): HasOne
    {
        return $this->hasOne(Multa::class);
    }

    protected function casts(): array
    {
        return [
            'situacao' => EmprestimoSituacao::class,
            'devolvido_em' => 'immutable_datetime',
            'encerrado_em' => 'immutable_datetime',
            'aviso_vencimento_em' => 'immutable_datetime',
            'prazo' => PrazoEmprestimoCast::class,
        ];
    }

    public function devolver(): void
    {
        if ($this->situacao === EmprestimoSituacao::Devolvido) {
            throw new DomainException('Empréstimo já foi devolvido');
        }

        if ($this->situacao === EmprestimoSituacao::Encerrado) {
            throw new DomainException('O exemplar deste empréstimo foi baixado do acervo.');
        }

        $this->situacao = EmprestimoSituacao::Devolvido;
        $this->devolvido_em = CarbonImmutable::now();
    }

    public function encerrarPorBaixaDoExemplar(): void
    {
        if ($this->situacao === EmprestimoSituacao::Devolvido) {
            throw new DomainException('Empréstimo já foi devolvido');
        }

        if ($this->situacao === EmprestimoSituacao::Encerrado) {
            throw new DomainException('Empréstimo já foi encerrado');
        }

        $this->situacao = EmprestimoSituacao::Encerrado;
        $this->encerrado_em = CarbonImmutable::now();
    }

    public function marcarAtrasado(): void
    {
        if ($this->situacao !== EmprestimoSituacao::Andamento) {
            throw new DomainException('Empréstimo deve estar em andamento para ser marcado como atrasado.');
        }

        $this->situacao = EmprestimoSituacao::Atrasado;
    }

    public function marcarAvisoVencimento(): void
    {
        $this->aviso_vencimento_em = CarbonImmutable::now();
    }

    public function renovar(bool $temReservaPendente, bool $temMultaPendente): void
    {
        if ($this->situacao === EmprestimoSituacao::Devolvido) {
            throw new DomainException('Não é possível renovar um empréstimo devolvido.');
        }

        if ($this->situacao === EmprestimoSituacao::Encerrado) {
            throw new DomainException('O exemplar deste empréstimo foi baixado do acervo.');
        }

        if ($this->prazo->estaAtrasado()) {
            throw new DomainException('Não é possível renovar um empréstimo atrasado.');
        }

        if ($this->qtd_renovacoes >= self::MAX_RENOVACOES) {
            throw new DomainException('O máximo de renovações foi atingido.');
        }

        if (! $this->prazo->estaNaJanelaDeRenovacao()) {
            $liberaEm = $this->prazo->inicioDaJanelaDeRenovacao()->format('d/m/Y');

            throw new DomainException("A renovação fica disponível a partir de {$liberaEm}.");
        }

        if ($temMultaPendente || $temReservaPendente) {
            $mensagem = $temMultaPendente ? 'Não é possível renovar um empréstimo com multa pendente.' : 'Existe uma reserva pendente para esta obra.';

            throw new DomainException($mensagem);
        }

        $prazo = $this->prazo->estender();
        $this->prazo = $prazo;
        $this->qtd_renovacoes++;
        $this->aviso_vencimento_em = null;
    }

    #[Scope]
    protected function doUsuario(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    #[Scope]
    protected function ativoPorExemplar(Builder $query, int $exemplarId): Builder
    {
        return $query->where('exemplar_id', $exemplarId)
            ->whereIn('situacao', [EmprestimoSituacao::Andamento, EmprestimoSituacao::Atrasado]);
    }

    #[Scope]
    protected function proximosDoVencimento(Builder $query, ?DateTimeInterface $referencia = null): Builder
    {
        $referencia = $referencia ? CarbonImmutable::instance($referencia) : CarbonImmutable::now();

        return $query->where('situacao', EmprestimoSituacao::Andamento)
            ->whereNull('aviso_vencimento_em')
            ->whereBetween('prazo_devolucao', [
                $referencia->startOfDay(),
                $referencia->addDay()->endOfDay(),
            ]);
    }

    #[Scope]
    protected function vencidosEmAndamento(Builder $query, ?DateTimeInterface $referencia = null): Builder
    {
        return $query->where('situacao', EmprestimoSituacao::Andamento)
            ->where('prazo_devolucao', '<', $referencia ?? CarbonImmutable::now());
    }

    #[Scope]
    protected function ativosPorUsuario(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId)
            ->whereIn('situacao', [EmprestimoSituacao::Andamento, EmprestimoSituacao::Atrasado]);
    }
}
