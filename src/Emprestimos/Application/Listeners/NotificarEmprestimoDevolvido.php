<?php

namespace Emprestimos\Application\Listeners;

use DomainException;
use Emprestimos\Application\Notifications\EmprestimoDevolvido;
use Emprestimos\Domain\Events\EmprestimoFoiDevolvido;
use Illuminate\Contracts\Queue\ShouldQueue;
use Inventario\Domain\Services\AcervoService;

class NotificarEmprestimoDevolvido implements ShouldQueue
{
    public int $tries = 5;

    public int $backoff = 60;

    public function __construct(
        private readonly AcervoService $acervo,
    ) {}

    public function __invoke(EmprestimoFoiDevolvido $evento): void
    {
        $emprestimo = $evento->emprestimo;

        $obra = $this->acervo->obraDoExemplar($emprestimo->exemplar_id);

        if ($obra === null) {
            throw new DomainException("Exemplar {$emprestimo->exemplar_id} não existe no acervo.");
        }

        if ($emprestimo->devolvido_em === null) {
            throw new DomainException("Empréstimo {$emprestimo->id} foi devolvido sem data de devolução.");
        }

        $emprestimo->user->notify(new EmprestimoDevolvido($obra->titulo, $emprestimo->devolvido_em));
    }
}
