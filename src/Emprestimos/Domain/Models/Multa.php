<?php

namespace Emprestimos\Domain\Models;

use App\Models\User;
use Carbon\CarbonImmutable;
use Database\Factories\MultaFactory;
use DomainException;
use Emprestimos\Domain\Enums\MultaSituacao;
use Emprestimos\Domain\Observers\MultaObserver;
use Emprestimos\Domain\ValueObjects\Dinheiro;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $emprestimo_id
 * @property int $user_id
 * @property MultaSituacao $situacao
 * @property Dinheiro $valor
 * @property int $dias_atraso
 * @property CarbonImmutable|null $paga_em
 * @property-read Emprestimo $emprestimo
 * @property-read User $user
 */
#[ObservedBy([MultaObserver::class])]
#[Fillable(['emprestimo_id', 'user_id', 'valor', 'dias_atraso'])]
class Multa extends Model
{
    /** @use HasFactory<MultaFactory> */
    use HasFactory;

    protected $attributes = [
        'situacao' => MultaSituacao::Pendente->value,
    ];

    /**
     * @return BelongsTo<Emprestimo, $this>
     */
    public function emprestimo(): BelongsTo
    {
        return $this->belongsTo(Emprestimo::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function pagar(): void
    {
        if ($this->situacao === MultaSituacao::Paga) {
            throw new DomainException('Multa já está paga');
        }

        $this->situacao = MultaSituacao::Paga;
        $this->paga_em = CarbonImmutable::now();
    }

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'situacao' => MultaSituacao::class,
            'valor' => Dinheiro::class.':BRL',
            'paga_em' => 'datetime',
        ];
    }

    /**
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    #[Scope]
    protected function doUsuario(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    /**
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    #[Scope]
    protected function pendentePorUsuario(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId)->where('situacao', MultaSituacao::Pendente);
    }
}
