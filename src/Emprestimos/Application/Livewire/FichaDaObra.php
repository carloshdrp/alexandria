<?php

namespace Emprestimos\Application\Livewire;

use App\Acesso\Domain\Enums\UsuarioPapel;
use App\Concerns\ExibeExcecaoDeDominio;
use App\Models\User;
use Emprestimos\Domain\Models\Reserva;
use Emprestimos\Domain\Services\RealizacaoEmprestimoService;
use Emprestimos\Domain\Services\ReservaObraService;
use Illuminate\Contracts\View\View;
use Inventario\Domain\Services\AcervoService;
use Inventario\Domain\ValueObjects\ObraDoAcervo;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Ficha da obra')]
class FichaDaObra extends Component
{
    use ExibeExcecaoDeDominio;

    public int $obraId = 0;

    public ?int $exemplarId = null;

    public string $cliente = '';

    public function mount(int $obra): void
    {
        $this->obraId = $obra;
    }

    public function reservarParaCliente(ReservaObraService $service): void
    {
        $this->authorize('criarParaOutro', Reserva::class);

        $dados = $this->validate([
            'cliente' => ['required', 'string'],
        ], [], ['cliente' => 'cliente']);

        $cliente = $this->localizarCliente($dados['cliente']);

        if ($cliente === null) {
            $this->addError('cliente', 'Nenhum cliente encontrado com esse e-mail ou CPF.');

            return;
        }

        $service->reservar($cliente, $this->obra());

        $this->reset('cliente');

        session()->flash('sucesso', "Reserva registrada para {$cliente->name}.");
    }

    private function localizarCliente(string $termo): ?User
    {
        $termo = trim($termo);
        $digitos = preg_replace('/\D/', '', $termo) ?? '';

        return User::query()
            ->where('papel', UsuarioPapel::Cliente)
            ->where(function ($query) use ($termo, $digitos) {
                $query->where('email', mb_strtolower($termo));

                if ($digitos !== '') {
                    $query->orWhere('documento', $digitos);
                }
            })
            ->first();
    }

    public function reservar(ReservaObraService $service): void
    {
        $service->reservar($this->usuario(), $this->obra());

        session()->flash('sucesso', 'Reserva registrada. Você será avisado por e-mail quando um exemplar ficar disponível.');
    }

    private function usuario(): User
    {
        $usuario = auth()->user();

        abort_if($usuario === null, 403);

        return $usuario;
    }

    private function obra(): ObraDoAcervo
    {
        $obra = app(AcervoService::class)->obra($this->obraId);

        abort_if($obra === null, 404);

        return $obra;
    }

    public function emprestar(RealizacaoEmprestimoService $service, AcervoService $acervo): void
    {
        $this->authorize('bibliotecario');

        $dados = $this->validate([
            'exemplarId' => ['required', 'integer'],
            'cliente' => ['required', 'string'],
        ], [], ['exemplarId' => 'exemplar', 'cliente' => 'cliente']);

        $cliente = $this->localizarCliente($dados['cliente']);

        if ($cliente === null) {
            $this->addError('cliente', 'Nenhum cliente encontrado com esse e-mail ou CPF.');

            return;
        }

        $exemplar = $acervo->exemplar($dados['exemplarId']);

        if ($exemplar === null || $exemplar->obraId !== $this->obraId) {
            $this->addError('exemplarId', 'Exemplar inválido.');

            return;
        }

        $service->realizar($cliente, $exemplar);

        $this->reset('exemplarId', 'cliente');

        session()->flash('sucesso', "Empréstimo registrado para {$cliente->name}.");
    }

    public function render(AcervoService $acervo, ReservaObraService $reservas): View
    {
        $obra = $this->obra();
        $usuario = $this->usuario();

        return view('emprestimos::livewire.ficha-da-obra', [
            'obra' => $obra,
            'exemplares' => $acervo->exemplaresDaObra($obra->id),
            'tamanhoDaFila' => Reserva::proximaReserva($obra->id)->count(),
            'jaReservou' => Reserva::reservaPorObra($obra->id)->where('user_id', $usuario->id)->exists(),
            'jaEmprestado' => $reservas->temExemplarEmprestado($usuario, $obra),
        ]);
    }
}
