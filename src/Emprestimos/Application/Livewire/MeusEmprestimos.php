<?php

namespace Emprestimos\Application\Livewire;

use App\Concerns\ExibeExcecaoDeDominio;
use Emprestimos\Domain\Enums\EmprestimoSituacao;
use Emprestimos\Domain\Models\Emprestimo;
use Emprestimos\Domain\Services\RenovacaoEmprestimoService;
use Illuminate\Contracts\View\View;
use Inventario\Domain\Services\AcervoService;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Title('Meus empréstimos')]
class MeusEmprestimos extends Component
{
    use ExibeExcecaoDeDominio;

    #[Url(except: 'ativos')]
    public string $aba = 'ativos';

    public function renovar(int $emprestimoId, RenovacaoEmprestimoService $service): void
    {
        $emprestimo = Emprestimo::findOrFail($emprestimoId);

        $this->authorize('renovar', $emprestimo);

        $service->renovar($emprestimo);

        session()->flash('sucesso', 'Empréstimo renovado até '.$emprestimo->prazo->prazoDevolucao()->format('d/m/Y').'.');
    }

    public function render(AcervoService $acervo): View
    {
        $usuario = auth()->user();

        abort_if($usuario === null, 403);

        $consulta = Emprestimo::doUsuario($usuario->id)->with(['renovacoes', 'multa'])->latest('retirado_em');

        $emprestimos = $this->aba === 'historico'
            ? $consulta->whereIn('situacao', [EmprestimoSituacao::Devolvido, EmprestimoSituacao::Encerrado])->get()
            : $consulta->whereIn('situacao', [EmprestimoSituacao::Andamento, EmprestimoSituacao::Atrasado])->get();

        return view('emprestimos::livewire.meus-emprestimos', [
            'emprestimos' => $emprestimos,
            'exemplares' => $acervo->exemplares($emprestimos->pluck('exemplar_id')->all()),
        ]);
    }
}
