<?php

namespace Emprestimos\Application\Livewire;

use Acesso\Domain\Events\UsuarioFoiBloqueado;
use Acesso\Domain\Events\UsuarioFoiDesbloqueado;
use Acesso\Domain\Models\User;
use App\Concerns\ExibeExcecaoDeDominio;
use Emprestimos\Domain\Enums\EmprestimoSituacao;
use Emprestimos\Domain\Enums\MultaSituacao;
use Emprestimos\Domain\Enums\ReservaSituacao;
use Emprestimos\Domain\Events\MultaFoiPaga;
use Emprestimos\Domain\Models\Emprestimo;
use Emprestimos\Domain\Models\Multa;
use Emprestimos\Domain\Models\Reserva;
use Emprestimos\Domain\Services\AtendimentoReservaService;
use Emprestimos\Domain\Services\CancelamentoReservaService;
use Emprestimos\Domain\Services\DevolucaoEmprestimoService;
use Emprestimos\Domain\Services\RealizacaoEmprestimoService;
use Emprestimos\Domain\Services\RenovacaoEmprestimoService;
use Illuminate\Contracts\View\View;
use Inventario\Domain\Services\AcervoService;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Situação do cliente')]
class SituacaoDoCliente extends Component
{
    use ExibeExcecaoDeDominio;

    public ?User $user = null;

    public string $codigoPatrimonio = '';

    public function mount(User $user): void
    {
        abort_if($user->ehBibliotecario(), 404);

        $this->user = $user;
    }

    public function emprestarPorCodigo(RealizacaoEmprestimoService $service, AcervoService $acervo): void
    {
        $dados = $this->validate(['codigoPatrimonio' => ['required', 'string']], [], ['codigoPatrimonio' => 'código de patrimônio']);

        $exemplar = $acervo->exemplarPorCodigo($dados['codigoPatrimonio']);

        if ($exemplar === null) {
            $this->addError('codigoPatrimonio', 'Nenhum exemplar com esse código.');

            return;
        }

        $service->realizar($this->cliente(), $exemplar);

        $this->reset('codigoPatrimonio');

        session()->flash('sucesso', "Empréstimo de \"{$exemplar->tituloObra}\" registrado.");
    }

    private function cliente(): User
    {
        abort_if($this->user === null, 404);

        return $this->user;
    }

    public function devolver(int $emprestimoId, DevolucaoEmprestimoService $service): void
    {
        $emprestimo = $this->emprestimoDoCliente($emprestimoId);

        $this->authorize('devolver', $emprestimo);

        $service->devolver($emprestimo);

        session()->flash('sucesso', $emprestimo->multa()->exists() ? 'Devolução registrada. Multa por atraso gerada.' : 'Devolução registrada.');
    }

    private function emprestimoDoCliente(int $emprestimoId): Emprestimo
    {
        return Emprestimo::doUsuario($this->cliente()->id)->findOrFail($emprestimoId);
    }

    public function renovar(int $emprestimoId, RenovacaoEmprestimoService $service): void
    {
        $emprestimo = $this->emprestimoDoCliente($emprestimoId);

        $this->authorize('renovar', $emprestimo);

        $service->renovar($emprestimo);

        session()->flash('sucesso', 'Empréstimo renovado até '.$emprestimo->prazo->prazoDevolucao()->format('d/m/Y').'.');
    }

    public function registrarPagamento(int $multaId): void
    {
        $multa = Multa::doUsuario($this->cliente()->id)->findOrFail($multaId);

        $this->authorize('pagar', $multa);

        $multa->pagar();
        $multa->save();

        event(new MultaFoiPaga($multa));

        session()->flash('sucesso', "Pagamento de {$multa->valor} registrado.");
    }

    public function atenderReserva(int $reservaId, AtendimentoReservaService $service): void
    {
        $reserva = $this->reservaDoCliente($reservaId);

        $this->authorize('atender', $reserva);

        $service->atender($reserva);

        session()->flash('sucesso', 'Reserva atendida: empréstimo registrado.');
    }

    private function reservaDoCliente(int $reservaId): Reserva
    {
        return Reserva::doUsuario($this->cliente()->id)->findOrFail($reservaId);
    }

    public function cancelarReserva(int $reservaId, CancelamentoReservaService $service): void
    {
        $reserva = $this->reservaDoCliente($reservaId);

        $this->authorize('cancelar', $reserva);

        $service->cancelar($reserva);

        session()->flash('sucesso', 'Reserva cancelada.');
    }

    public function bloquear(): void
    {
        $this->authorize('bibliotecario');

        $cliente = $this->cliente();

        $cliente->bloquear();
        $cliente->save();

        event(new UsuarioFoiBloqueado($cliente));

        session()->flash('sucesso', 'Cliente bloqueado.');
    }

    public function desbloquear(): void
    {
        $this->authorize('bibliotecario');

        $cliente = $this->cliente();

        $cliente->desbloquear();
        $cliente->save();

        event(new UsuarioFoiDesbloqueado($cliente));

        session()->flash('sucesso', 'Cliente desbloqueado.');
    }

    public function render(AcervoService $acervo): View
    {
        $cliente = $this->cliente();

        $emprestimos = Emprestimo::doUsuario($cliente->id)->with(['renovacoes', 'multa'])->latest('retirado_em')->get();
        $multas = Multa::doUsuario($cliente->id)->with('emprestimo')->latest()->get();
        $reservas = Reserva::doUsuario($cliente->id)->latest()->get();

        return view('emprestimos::livewire.situacao-do-cliente', [
            'cliente' => $cliente,
            'ativos' => $emprestimos->whereIn('situacao', [EmprestimoSituacao::Andamento, EmprestimoSituacao::Atrasado]),
            'historico' => $emprestimos->whereIn('situacao', [EmprestimoSituacao::Devolvido, EmprestimoSituacao::Encerrado]),
            'multasPendentes' => $multas->where('situacao', MultaSituacao::Pendente),
            'multasPagas' => $multas->where('situacao', MultaSituacao::Paga),
            'reservasAtivas' => $reservas->whereIn('situacao', [ReservaSituacao::Aguardando, ReservaSituacao::Disponivel]),
            'exemplares' => $acervo->exemplares(
                $emprestimos->pluck('exemplar_id')->merge($multas->map(fn (Multa $multa) => $multa->emprestimo->exemplar_id))->unique()->all()
            ),
            'obras' => $reservas->pluck('obra_id')->unique()->mapWithKeys(fn (int $id) => [$id => $acervo->obra($id)->titulo ?? "Obra {$id}"]),
        ]);
    }
}
