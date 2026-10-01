<?php

namespace Emprestimos\Application\Livewire;

use App\Concerns\ExibeExcecaoDeDominio;
use Emprestimos\Domain\Enums\MultaSituacao;
use Emprestimos\Domain\Events\MultaFoiPaga;
use Emprestimos\Domain\Models\Multa;
use Illuminate\Contracts\View\View;
use Inventario\Domain\Services\AcervoService;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Multas pendentes')]
class MultasPendentes extends Component
{
    use ExibeExcecaoDeDominio;
    use WithPagination;

    public function registrarPagamento(int $multaId): void
    {
        $multa = Multa::findOrFail($multaId);

        $this->authorize('pagar', $multa);

        $multa->pagar();
        $multa->save();

        event(new MultaFoiPaga($multa));

        session()->flash('sucesso', "Pagamento de {$multa->valor} registrado.");
    }

    public function render(AcervoService $acervo): View
    {
        $multas = Multa::query()
            ->with(['user', 'emprestimo'])
            ->where('situacao', MultaSituacao::Pendente)
            ->oldest()
            ->paginate(20);

        return view('emprestimos::livewire.multas-pendentes', [
            'multas' => $multas,
            'exemplares' => $acervo->exemplares($multas->getCollection()->map(fn (Multa $multa) => $multa->emprestimo->exemplar_id)->all()),
        ]);
    }
}
