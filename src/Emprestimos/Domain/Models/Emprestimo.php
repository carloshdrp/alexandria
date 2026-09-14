<?php

namespace Emprestimos\Domain\Models;

use App\Models\User;
use Carbon\CarbonImmutable;
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
use Inventario\Domain\Models\Exemplar;

#[ObservedBy([EmprestimoObserver::class])]
#[Fillable(['user_id', 'exemplar_id', 'retirado_em', 'prazo_devolucao'])]
class Emprestimo extends Model
{
    public const int MAX_RENOVACOES = 2;

    public const int MAX_ATIVOS_POR_USUARIO = 3;

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function exemplar(): BelongsTo
    {
        return $this->belongsTo(Exemplar::class);
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
            'retirado_em' => 'datetime',
            'prazo_devolucao' => 'date',
            'devolvido_em' => 'datetime',
            'prazo' => PrazoEmprestimoCast::class,
        ];
    }

    public function devolver(): void
    {
        if ($this->situacao === EmprestimoSituacao::Devolvido) {
            throw new DomainException('Empréstimo já foi devolvido');
        }

        $this->situacao = EmprestimoSituacao::Devolvido;
        $this->devolvido_em = CarbonImmutable::now();
    }

    public function marcarAtrasado(): void
    {
        if ($this->situacao !== EmprestimoSituacao::Andamento) {
            throw new DomainException('Empréstimo deve estar em andamento para ser marcado como atrasado.');
        }

        $this->situacao = EmprestimoSituacao::Atrasado;
    }

    public function renovar(bool $temReservaPendente, bool $temMultaPendente): void
    {
        if ($this->situacao === EmprestimoSituacao::Devolvido) {
            throw new DomainException('Não é possível renovar um empréstimo devolvido.');
        }

        if ($this->qtd_renovacoes >= self::MAX_RENOVACOES) {
            throw new DomainException('O máximo de renovações foi atingido.');
        }

        if ($temMultaPendente || $temReservaPendente) {
            $mensagem = $temMultaPendente ? 'Não é possível renovar um empréstimo com multa pendente.' : 'Existe uma reserva pendente para esta obra.';

            throw new DomainException($mensagem);
        }

        $prazo = $this->prazo->estender();
        $this->prazo = $prazo;
        $this->qtd_renovacoes++;
    }

    #[Scope]
    protected function ativosPorUsuario(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId)
            ->whereIn('situacao', [EmprestimoSituacao::Andamento, EmprestimoSituacao::Atrasado]);
    }
}
