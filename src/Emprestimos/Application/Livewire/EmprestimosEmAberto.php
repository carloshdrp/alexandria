<?php

namespace Emprestimos\Application\Livewire;

use App\Concerns\ExibeExcecaoDeDominio;
use Emprestimos\Domain\Enums\EmprestimoSituacao;
use Emprestimos\Domain\Models\Emprestimo;
use Emprestimos\Domain\Services\DevolucaoEmprestimoService;
use Emprestimos\Domain\Services\RenovacaoEmprestimoService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Inventario\Domain\Services\AcervoService;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Empréstimos em aberto')]
class EmprestimosEmAberto extends Component
{
    use ExibeExcecaoDeDominio;
    use WithPagination;

    #[Url(except: '')]
    public string $busca = '';

    #[Url(except: '')]
    public string $situacao = '';

    public function updated(string $propriedade): void
    {
        if (in_array($propriedade, ['busca', 'situacao'], true)) {
            $this->resetPage();
        }
    }

    public function devolver(int $emprestimoId, DevolucaoEmprestimoService $service): void
    {
        $emprestimo = Emprestimo::findOrFail($emprestimoId);

        $this->authorize('devolver', $emprestimo);

        $service->devolver($emprestimo);

        $mensagem = 'Devolução registrada.';

        if ($emprestimo->multa()->exists()) {
            $mensagem .= ' Multa por atraso gerada.';
        }

        session()->flash('sucesso', $mensagem);
    }

    public function renovar(int $emprestimoId, RenovacaoEmprestimoService $service): void
    {
        $emprestimo = Emprestimo::findOrFail($emprestimoId);

        $this->authorize('renovar', $emprestimo);

        $service->renovar($emprestimo);

        session()->flash('sucesso', 'Empréstimo renovado até '.$emprestimo->prazo->prazoDevolucao()->format('d/m/Y').'.');
    }

    public function render(AcervoService $acervo): View
    {
        $termo = trim($this->busca);
        $exemplarBuscado = $termo === '' ? null : $acervo->exemplarPorCodigo($termo);

        $consulta = Emprestimo::query()
            ->with('user')
            ->whereIn('situacao', $this->situacao === ''
                ? [EmprestimoSituacao::Andamento, EmprestimoSituacao::Atrasado]
                : [EmprestimoSituacao::from((int) $this->situacao)]);

        if ($exemplarBuscado !== null) {
            $consulta->where('exemplar_id', $exemplarBuscado->id);
        } elseif ($termo !== '') {
            $parecido = "%{$termo}%";

            $consulta->whereHas('user', fn (Builder $user) => $user->where('name', 'ilike', $parecido)->orWhere('email', 'ilike', $parecido));
        }

        $emprestimos = $consulta->orderBy('prazo_devolucao')->paginate(20);

        return view('emprestimos::livewire.emprestimos-em-aberto', [
            'emprestimos' => $emprestimos,
            'exemplares' => $acervo->exemplares($emprestimos->getCollection()->pluck('exemplar_id')->all()),
            'situacoes' => [EmprestimoSituacao::Andamento, EmprestimoSituacao::Atrasado, EmprestimoSituacao::Devolvido, EmprestimoSituacao::Encerrado],
        ]);
    }
}
