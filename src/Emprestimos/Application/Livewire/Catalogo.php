<?php

namespace Emprestimos\Application\Livewire;

use Illuminate\Contracts\View\View;
use Inventario\Domain\Services\AcervoService;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Catálogo')]
class Catalogo extends Component
{
    use WithPagination;

    #[Url(as: 'busca', except: '')]
    public string $busca = '';

    public function updatedBusca(): void
    {
        $this->resetPage();
    }

    public function render(AcervoService $acervo): View
    {
        return view('emprestimos::livewire.catalogo', [
            'obras' => $acervo->catalogo($this->busca),
        ]);
    }
}
