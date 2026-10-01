<?php

namespace Inventario\Application\Livewire;

use App\Concerns\ExibeExcecaoDeDominio;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;
use Inventario\Domain\Models\Autor;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Autores')]
class Autores extends Component
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
        $dados = $this->validate(['nome' => ['required', 'string', 'max:255', Rule::unique('autores', 'nome')]]);

        $usuario = auth()->user();

        abort_if(! $usuario instanceof User, 403);

        Autor::create(['nome' => $dados['nome'], 'user_id' => $usuario->id]);

        $this->reset('nome');

        session()->flash('sucesso', 'Autor cadastrado.');
    }

    public function editar(int $autorId): void
    {
        $autor = Autor::findOrFail($autorId);

        $this->resetValidation();

        $this->edicao_id = $autor->id;
        $this->edicao_nome = $autor->nome;
    }

    public function cancelarEdicao(): void
    {
        $this->reset('edicao_id', 'edicao_nome');
    }

    public function salvar(): void
    {
        $autor = Autor::findOrFail($this->edicao_id);

        $dados = $this->validate([
            'edicao_nome' => ['required', 'string', 'max:255', Rule::unique('autores', 'nome')->ignore($autor->id)],
        ], [], ['edicao_nome' => 'nome']);

        $autor->update(['nome' => $dados['edicao_nome']]);

        $this->cancelarEdicao();

        session()->flash('sucesso', 'Autor atualizado.');
    }

    public function remover(int $autorId): void
    {
        $autor = Autor::findOrFail($autorId);

        $autor->delete();

        session()->flash('sucesso', "Autor \"{$autor->nome}\" removido.");
    }

    public function render(): View
    {
        $termo = trim($this->busca);

        return view('inventario::livewire.autores', [
            'registros' => Autor::query()
                ->when($termo !== '', fn (Builder $query) => $query->where('nome', 'ilike', '%'.$termo.'%'))
                ->orderBy('nome')
                ->paginate(20),
        ]);
    }
}
