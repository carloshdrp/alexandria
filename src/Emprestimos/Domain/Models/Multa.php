<?php

namespace Emprestimos\Domain\Models;

use App\Casts\ValueObjectCast;
use App\Models\User;
use Carbon\CarbonImmutable;
use DomainException;
use Emprestimos\Domain\Enums\MultaSituacao;
use Emprestimos\Domain\Observers\MultaObserver;
use Emprestimos\Domain\ValueObjects\Dinheiro;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[ObservedBy([MultaObserver::class])]
#[Fillable(['emprestimo_id', 'user_id', 'valor', 'dias_atraso'])]
class Multa extends Model
{
    public function emprestimo(): BelongsTo
    {
        return $this->belongsTo(Emprestimo::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected function casts(): array
    {
        return [
            'situacao' => MultaSituacao::class,
            'valor' => ValueObjectCast::class.':'.Dinheiro::class,
            'paga_em' => 'datetime',
        ];
    }

    public function pagar(): void
    {
        if ($this->situacao === MultaSituacao::Paga) {
            throw new DomainException('Multa já está paga');
        }

        $this->situacao = MultaSituacao::Paga;
        $this->paga_em = CarbonImmutable::now();
    }

    #[Scope]
    protected function doUsuario(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    #[Scope]
    protected function pendentePorUsuario(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId)->where('situacao', MultaSituacao::Pendente);
    }
}
