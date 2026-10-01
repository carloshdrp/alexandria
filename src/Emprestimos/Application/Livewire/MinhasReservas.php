<?php

namespace Emprestimos\Application\Livewire;

use App\Concerns\ExibeExcecaoDeDominio;
use Emprestimos\Domain\Enums\ReservaSituacao;
use Emprestimos\Domain\Models\Reserva;
use Emprestimos\Domain\Services\CancelamentoReservaService;
use Illuminate\Contracts\View\View;
use Inventario\Domain\Services\AcervoService;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Minhas reservas')]
class MinhasReservas extends Component
{
    use ExibeExcecaoDeDominio;

    public function cancelar(int $reservaId, CancelamentoReservaService $service): void
    {
        $reserva = Reserva::findOrFail($reservaId);

        $this->authorize('cancelar', $reserva);

        $service->cancelar($reserva);

        session()->flash('sucesso', 'Reserva cancelada.');
    }

    public function render(AcervoService $acervo): View
    {
        $usuario = auth()->user();

        abort_if($usuario === null, 403);

        $reservas = Reserva::doUsuario($usuario->id)->latest()->get();

        $posicoes = $reservas
            ->where('situacao', ReservaSituacao::Aguardando)
            ->mapWithKeys(fn (Reserva $reserva) => [
                $reserva->id => Reserva::proximaReserva($reserva->obra_id)->where('created_at', '<', $reserva->created_at)->count() + 1,
            ]);

        return view('emprestimos::livewire.minhas-reservas', [
            'ativas' => $reservas->whereIn('situacao', [ReservaSituacao::Aguardando, ReservaSituacao::Disponivel]),
            'encerradas' => $reservas->whereNotIn('situacao', [ReservaSituacao::Aguardando, ReservaSituacao::Disponivel]),
            'obras' => $reservas->pluck('obra_id')->unique()->mapWithKeys(fn (int $id) => [$id => $acervo->obra($id)->titulo ?? "Obra {$id}"]),
            'posicoes' => $posicoes,
        ]);
    }
}
