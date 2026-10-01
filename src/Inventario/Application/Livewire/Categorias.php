<?php

namespace Inventario\Application\Livewire;

use App\Concerns\ExibeExcecaoDeDominio;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;
use Inventario\Domain\Models\Categoria;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Categorias')]
class Categorias extends Component
{
    use ExibeExcecaoDeDominio;
    use WithPagination;

    public string $nome = '';

    public ?int $edicao_id = null;

    public string $edicao_nome = '';

    #[Url(except: '')]
    public string $busca = '';

    public function updatedBusca(): void
    {
        $this->cancelarEdicao();
        $this->resetPage();
    }

    public function cadastrar(): void
    {
        $dados = $this->validate(['nome' => ['required', 'string', 'max:255', Rule::unique('categorias', 'nome')]]);

        $usuario = auth()->user();

        abort_if(! $usuario instanceof User, 403);

        Categoria::create(['nome' => $dados['nome'], 'user_id' => $usuario->id]);

        $this->reset('nome');

        session()->flash('sucesso', 'Categoria cadastrada.');
    }

    public function editar(int $categoriaId): void
    {
        $categoria = Categoria::findOrFail($categoriaId);

        $this->resetValidation();

        $this->edicao_id = $categoria->id;
        $this->edicao_nome = $categoria->nome;
    }

    public function cancelarEdicao(): void
    {
        $this->reset('edicao_id', 'edicao_nome');
    }

    public function salvar(): void
    {
        $categoria = Categoria::findOrFail($this->edicao_id);

        $dados = $this->validate([
            'edicao_nome' => ['required', 'string', 'max:255', Rule::unique('categorias', 'nome')->ignore($categoria->id)],
        ], [], ['edicao_nome' => 'nome']);

        $categoria->update(['nome' => $dados['edicao_nome']]);

        $this->cancelarEdicao();

        session()->flash('sucesso', 'Categoria atualizada.');
    }

    public function remover(int $categoriaId): void
    {
        $categoria = Categoria::findOrFail($categoriaId);

        $categoria->delete();

        session()->flash('sucesso', "Categoria \"{$categoria->nome}\" removida.");
    }

    public function render(): View
    {
        $termo = trim($this->busca);

        return view('inventario::livewire.categorias', [
            'registros' => Categoria::query()
                ->when($termo !== '', fn (Builder $query) => $query->where('nome', 'ilike', '%'.$termo.'%'))
                ->orderBy('nome')
                ->paginate(20),
        ]);
    }
}
