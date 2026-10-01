<?php

namespace Inventario\Application\Livewire;

use App\Concerns\ExibeExcecaoDeDominio;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;
use Inventario\Domain\Models\Editora;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Editoras')]
class Editoras extends Component
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
        $dados = $this->validate(['nome' => ['required', 'string', 'max:255', Rule::unique('editoras', 'nome')]]);

        $usuario = auth()->user();

        abort_if(! $usuario instanceof User, 403);

        Editora::create(['nome' => $dados['nome'], 'user_id' => $usuario->id]);

        $this->reset('nome');

        session()->flash('sucesso', 'Editora cadastrada.');
    }

    public function editar(int $editoraId): void
    {
        $editora = Editora::findOrFail($editoraId);

        $this->resetValidation();

        $this->edicao_id = $editora->id;
        $this->edicao_nome = $editora->nome;
    }

    public function cancelarEdicao(): void
    {
        $this->reset('edicao_id', 'edicao_nome');
    }

    public function salvar(): void
    {
        $editora = Editora::findOrFail($this->edicao_id);

        $dados = $this->validate([
            'edicao_nome' => ['required', 'string', 'max:255', Rule::unique('editoras', 'nome')->ignore($editora->id)],
        ], [], ['edicao_nome' => 'nome']);

        $editora->update(['nome' => $dados['edicao_nome']]);

        $this->cancelarEdicao();

        session()->flash('sucesso', 'Editora atualizada.');
    }

    public function remover(int $editoraId): void
    {
        $editora = Editora::findOrFail($editoraId);

        $editora->delete();

        session()->flash('sucesso', "Editora \"{$editora->nome}\" removida.");
    }

    public function render(): View
    {
        $termo = trim($this->busca);

        return view('inventario::livewire.editoras', [
            'registros' => Editora::query()
                ->when($termo !== '', fn (Builder $query) => $query->where('nome', 'ilike', '%'.$termo.'%'))
                ->orderBy('nome')
                ->paginate(20),
        ]);
    }
}
