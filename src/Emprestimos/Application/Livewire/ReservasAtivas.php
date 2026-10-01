<?php

namespace Emprestimos\Application\Livewire;

use App\Concerns\ExibeExcecaoDeDominio;
use Emprestimos\Domain\Enums\ReservaSituacao;
use Emprestimos\Domain\Models\Reserva;
use Emprestimos\Domain\Services\AtendimentoReservaService;
use Emprestimos\Domain\Services\CancelamentoReservaService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Inventario\Domain\Services\AcervoService;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Reservas ativas')]
class ReservasAtivas extends Component
{
    use ExibeExcecaoDeDominio;
    use WithPagination;

    #[Url(except: '')]
    public string $busca = '';

    public function updatedBusca(): void
    {
        $this->resetPage();
    }

    public function atender(int $reservaId, AtendimentoReservaService $service): void
    {
        $reserva = Reserva::findOrFail($reservaId);

        $this->authorize('atender', $reserva);

        $service->atender($reserva);

        session()->flash('sucesso', "Reserva atendida: empréstimo registrado para {$reserva->user->name}.");
    }

    public function cancelar(int $reservaId, CancelamentoReservaService $service): void
    {
        $reserva = Reserva::findOrFail($reservaId);

        $this->authorize('cancelar', $reserva);

        $service->cancelar($reserva);

        session()->flash('sucesso', 'Reserva cancelada.');
    }

    public function render(AcervoService $acervo): View
    {
        $termo = trim($this->busca);
        $exemplarBuscado = $termo === '' ? null : $acervo->exemplarPorCodigo($termo);

        $consulta = Reserva::query()
            ->with('user')
            ->whereIn('situacao', [ReservaSituacao::Aguardando, ReservaSituacao::Disponivel]);

        if ($exemplarBuscado !== null) {
            $consulta->where('exemplar_id', $exemplarBuscado->id);
        } elseif ($termo !== '') {
            $parecido = "%{$termo}%";

            $consulta->whereHas('user', fn (Builder $user) => $user->where('name', 'ilike', $parecido)->orWhere('email', 'ilike', $parecido));
        }

        $reservas = $consulta->orderBy('obra_id')->oldest()->paginate(20);

        return view('emprestimos::livewire.reservas-ativas', [
            'reservas' => $reservas,
            'obras' => $reservas->getCollection()->pluck('obra_id')->unique()->mapWithKeys(fn (int $id) => [$id => $acervo->obra($id)->titulo ?? "Obra {$id}"]),
        ]);
    }
}
