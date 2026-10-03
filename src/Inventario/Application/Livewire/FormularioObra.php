<?php

namespace Inventario\Application\Livewire;

use Acesso\Domain\Models\User;
use App\Concerns\ExibeExcecaoDeDominio;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Inventario\Domain\Models\Autor;
use Inventario\Domain\Models\Categoria;
use Inventario\Domain\Models\Editora;
use Inventario\Domain\Models\Obra;
use Inventario\Domain\Services\AtualizacaoObraService;
use Inventario\Domain\Services\CadastroObraService;
use Inventario\Infrastructure\Rules\IsbnValido;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

#[Title('Obra')]
class FormularioObra extends Component
{
    use ExibeExcecaoDeDominio;
    use WithFileUploads;

    public ?Obra $obra = null;

    public string $titulo = '';

    public string $isbn = '';

    public string $editora_id = '';

    public string $categoria_id = '';

    public string $ano_publicacao = '';

    /** @var list<int> */
    public array $autores = [];

    public string $autor_id = '';

    public ?TemporaryUploadedFile $capa = null;

    public function mount(?Obra $obra = null): void
    {
        if ($obra === null || ! $obra->exists) {
            return;
        }

        $this->obra = $obra;
        $this->titulo = $obra->titulo;
        $this->isbn = $obra->isbn?->getNativeValue() ?? '';
        $this->editora_id = (string) $obra->editora_id;
        $this->categoria_id = (string) $obra->categoria_id;
        $this->ano_publicacao = (string) ($obra->ano_publicacao ?? '');
        $this->autores = array_values($obra->autores->map(fn (Autor $autor) => $autor->id)->all());
    }

    public function adicionarAutor(): void
    {
        $autorId = (int) $this->autor_id;

        if ($autorId === 0 || in_array($autorId, $this->autores, true)) {
            return;
        }

        $this->autores[] = $autorId;
        $this->autor_id = '';
        $this->resetErrorBag('autores');
    }

    public function removerAutor(int $autorId): void
    {
        $this->autores = array_values(array_filter(
            $this->autores,
            fn (int $id) => $id !== $autorId
        ));
    }

    public function moverAutor(int $posicao, int $destino): void
    {
        if (! isset($this->autores[$posicao], $this->autores[$destino])) {
            return;
        }

        $autores = $this->autores;

        [$autores[$posicao], $autores[$destino]] = [$autores[$destino], $autores[$posicao]];

        $this->autores = array_values($autores);
    }

    public function salvar(CadastroObraService $cadastro, AtualizacaoObraService $atualizacao): void
    {
        $this->isbn = strtoupper(preg_replace('/[^0-9Xx]/', '', $this->isbn) ?? '');

        $dados = $this->validate($this->regras(), [], $this->atributos());

        $dados['isbn'] = $dados['isbn'] === '' ? null : $dados['isbn'];
        $dados['ano_publicacao'] = $dados['ano_publicacao'] === '' ? null : (int) $dados['ano_publicacao'];
        $dados['autores'] = array_map('intval', $dados['autores']);

        $capa = $this->capa;

        if ($this->obra === null) {
            $usuario = auth()->user();

            abort_if(! $usuario instanceof User, 403);

            $obra = $cadastro->cadastrar($usuario, $dados, $capa);

            session()->flash('sucesso', "Obra \"{$obra->titulo}\" cadastrada.");
        } else {
            $obra = $atualizacao->atualizar($this->obra, $dados, $capa);

            session()->flash('sucesso', "Obra \"{$obra->titulo}\" atualizada.");
        }

        $this->redirectRoute('inventario.obras.exemplares', $obra);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    private function regras(): array
    {
        $isbnUnico = Rule::unique('obras', 'isbn');

        if ($this->obra !== null) {
            $isbnUnico->ignore($this->obra->id);
        }

        return [
            'titulo' => ['required', 'string', 'max:255'],
            'isbn' => ['nullable', 'string', new IsbnValido, $isbnUnico],
            'editora_id' => ['required', 'integer', 'exists:editoras,id'],
            'categoria_id' => ['required', 'integer', 'exists:categorias,id'],
            'ano_publicacao' => ['nullable', 'integer', 'min:1450', 'max:'.date('Y')],
            'autores' => ['required', 'array', 'min:1'],
            'autores.*' => ['integer', 'distinct', 'exists:autores,id'],
            'capa' => ['nullable', 'image', 'mimes:jpeg,png,webp', 'max:5120'],
        ];
    }

    /**
     * @return array<string, string>
     */
    private function atributos(): array
    {
        return [
            'titulo' => 'título',
            'isbn' => 'ISBN',
            'editora_id' => 'editora',
            'categoria_id' => 'categoria',
            'ano_publicacao' => 'ano de publicação',
            'autores' => 'autores',
            'capa' => 'capa',
        ];
    }

    public function render(): View
    {
        $todosAutores = Autor::orderBy('nome')->get();

        return view('inventario::livewire.formulario-obra', [
            'editoras' => Editora::orderBy('nome')->get(),
            'categorias' => Categoria::orderBy('nome')->get(),
            'todosAutores' => $todosAutores,
            'autoresSelecionados' => collect($this->autores)
                ->map(fn (int $id) => $todosAutores->firstWhere('id', $id))
                ->filter()
                ->values(),
        ]);
    }
}
