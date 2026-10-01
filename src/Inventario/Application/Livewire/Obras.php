<?php

namespace Inventario\Application\Livewire;

use App\Concerns\ExibeExcecaoDeDominio;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Inventario\Domain\Enums\ExemplarSituacao;
use Inventario\Domain\Events\ObraFoiRemovida;
use Inventario\Domain\Models\Obra;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Obras')]
class Obras extends Component
{
    use ExibeExcecaoDeDominio;
    use WithPagination;

    #[Url(except: '')]
    public string $busca = '';

    public function updatedBusca(): void
    {
        $this->resetPage();
    }

    public function remover(int $obraId): void
    {
        $obra = Obra::findOrFail($obraId);

        $obra->delete();

        event(new ObraFoiRemovida($obra));

        session()->flash('sucesso', "Obra \"{$obra->titulo}\" removida.");
    }

    public function render(): View
    {
        $obras = Obra::query()
            ->with(['autores', 'editora', 'categoria'])
            ->withCount([
                'exemplares as exemplares_total' => fn (Builder $query) => $query->where('situacao', '!=', ExemplarSituacao::Baixado),
                'exemplares as exemplares_disponiveis' => fn (Builder $query) => $query->where('situacao', ExemplarSituacao::NoAcervo),
            ])
            ->when(trim($this->busca) !== '', fn (Builder $query) => $query->where('titulo', 'ilike', '%'.trim($this->busca).'%'))
            ->orderBy('titulo')
            ->paginate(20);

        return view('inventario::livewire.obras', ['obras' => $obras]);
    }
}
