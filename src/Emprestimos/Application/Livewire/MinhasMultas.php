<?php

namespace Emprestimos\Application\Livewire;

use Emprestimos\Domain\Enums\MultaSituacao;
use Emprestimos\Domain\Models\Multa;
use Illuminate\Contracts\View\View;
use Inventario\Domain\Services\AcervoService;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Minhas multas')]
class MinhasMultas extends Component
{
    public function render(AcervoService $acervo): View
    {
        $usuario = auth()->user();

        abort_if($usuario === null, 403);

        $multas = Multa::doUsuario($usuario->id)->with('emprestimo')->latest()->get();

        return view('emprestimos::livewire.minhas-multas', [
            'pendentes' => $multas->where('situacao', MultaSituacao::Pendente),
            'pagas' => $multas->where('situacao', MultaSituacao::Paga),
            'exemplares' => $acervo->exemplares($multas->map(fn (Multa $multa) => $multa->emprestimo->exemplar_id)->all()),
        ]);
    }
}
