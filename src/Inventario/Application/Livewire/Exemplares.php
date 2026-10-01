<?php

namespace Inventario\Application\Livewire;

use App\Concerns\ExibeExcecaoDeDominio;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Inventario\Domain\Models\Exemplar;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Exemplares')]
class Exemplares extends Component
{
    use ExibeExcecaoDeDominio;
    use WithPagination;

    #[Url(except: '')]
    public string $busca = '';

    public function updatedBusca(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        $exemplares = Exemplar::query()
            ->with(['Obra'])
            ->when(trim($this->busca) !== '', fn (Builder $query) => $query->where('codigo_patrimonio', 'ilike', '%'.trim($this->busca).'%'))
            ->orderBy('codigo_patrimonio')
            ->paginate(20);

        return view('inventario::livewire.exemplares', ['exemplares' => $exemplares]);
    }
}
