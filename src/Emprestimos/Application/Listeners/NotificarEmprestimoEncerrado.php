<?php

namespace Emprestimos\Application\Listeners;

use DomainException;
use Emprestimos\Application\Notifications\EmprestimoEncerrado;
use Emprestimos\Domain\Events\EmprestimoFoiEncerradoPorBaixa;
use Illuminate\Contracts\Queue\ShouldQueue;
use Inventario\Domain\Services\AcervoService;

class NotificarEmprestimoEncerrado implements ShouldQueue
{
    public int $tries = 5;

    public int $backoff = 60;

    public function __construct(
        private readonly AcervoService $acervo,
    ) {}

    public function __invoke(EmprestimoFoiEncerradoPorBaixa $evento): void
    {
        $emprestimo = $evento->emprestimo;

        $exemplar = $this->acervo->exemplar($emprestimo->exemplar_id);

        if ($exemplar === null) {
            throw new DomainException("Exemplar {$emprestimo->exemplar_id} não existe no acervo.");
        }

        $emprestimo->user->notify(new EmprestimoEncerrado($exemplar->tituloObra, $exemplar->codigoPatrimonio));
    }
}
