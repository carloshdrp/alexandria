<?php

namespace Inventario\Application\Livewire;

use App\Concerns\ExibeExcecaoDeDominio;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Inventario\Domain\Enums\ExemplarEstadoConservacao;
use Inventario\Domain\Enums\ExemplarMotivoBaixa;
use Inventario\Domain\Events\ExemplarFoiAtualizado;
use Inventario\Domain\Events\ExemplarFoiCadastrado;
use Inventario\Domain\Events\Integracao\ExemplarFoiBaixado;
use Inventario\Domain\Models\Exemplar;
use Inventario\Domain\Models\Obra;
use Inventario\Domain\ValueObjects\CodigoPatrimonio;
use Inventario\Infrastructure\Rules\CodigoPatrimonioValido;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Exemplares')]
class ExemplaresDaObra extends Component
{
    use ExibeExcecaoDeDominio;

    public ?Obra $obra = null;

    public string $codigo_patrimonio = '';

    public string $estado_conservacao = '';

    public function mount(Obra $obra): void
    {
        $this->obra = $obra;
    }

    public function cadastrar(): void
    {
        $this->codigo_patrimonio = strtoupper(trim($this->codigo_patrimonio));

        $dados = $this->validate([
            'codigo_patrimonio' => ['required', 'string', new CodigoPatrimonioValido, 'unique:exemplares,codigo_patrimonio'],
            'estado_conservacao' => ['required', Rule::enum(ExemplarEstadoConservacao::class)],
        ], [], ['codigo_patrimonio' => 'código de patrimônio', 'estado_conservacao' => 'estado de conservação']);

        $usuario = auth()->user();

        abort_if(! $usuario instanceof User, 403);

        $exemplar = Exemplar::create([
            'obra_id' => $this->obraAtual()->id,
            'codigo_patrimonio' => CodigoPatrimonio::fromNative($dados['codigo_patrimonio']),
            'estado_conservacao' => ExemplarEstadoConservacao::from((int) $dados['estado_conservacao']),
            'user_id' => $usuario->id,
        ]);

        event(new ExemplarFoiCadastrado($exemplar));

        $this->reset('codigo_patrimonio', 'estado_conservacao');

        session()->flash('sucesso', "Exemplar {$exemplar->codigo_patrimonio} cadastrado.");
    }

    private function obraAtual(): Obra
    {
        abort_if($this->obra === null, 404);

        return $this->obra;
    }

    public function alterarConservacao(int $exemplarId, int $estado): void
    {
        $exemplar = $this->exemplarDaObra($exemplarId);

        $exemplar->alterarEstadoConservacao(ExemplarEstadoConservacao::from($estado));
        $exemplar->save();

        event(new ExemplarFoiAtualizado($exemplar));

        session()->flash('sucesso', "Exemplar {$exemplar->codigo_patrimonio} atualizado.");
    }

    private function exemplarDaObra(int $exemplarId): Exemplar
    {
        return $this->obraAtual()->exemplares()->findOrFail($exemplarId);
    }

    public function baixar(int $exemplarId, int $motivo): void
    {
        $exemplar = $this->exemplarDaObra($exemplarId);

        $exemplar->baixar(ExemplarMotivoBaixa::from($motivo));
        $exemplar->save();

        event(new ExemplarFoiBaixado($exemplar->id, $exemplar->obra_id));

        session()->flash('sucesso', "Exemplar {$exemplar->codigo_patrimonio} baixado.");
    }

    public function render(): View
    {
        $obra = $this->obraAtual();

        return view('inventario::livewire.exemplares-da-obra', [
            'obra' => $obra,
            'exemplares' => $obra->exemplares()->orderBy('codigo_patrimonio')->get(),
            'estados' => ExemplarEstadoConservacao::cases(),
            'motivos' => ExemplarMotivoBaixa::cases(),
        ]);
    }
}
