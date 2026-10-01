<?php

namespace App\Acesso\Application\Livewire\Clientes;

use App\Acesso\Domain\Enums\UsuarioPapel;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Clientes')]
class Clientes extends Component
{
    use WithPagination;

    #[Url(except: '')]
    public string $busca = '';

    public function updatedBusca(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        $termo = trim($this->busca);
        $digitos = preg_replace('/\D/', '', $termo) ?? '';

        $clientes = User::query()
            ->where('papel', UsuarioPapel::Cliente)
            ->when($termo !== '', function (Builder $query) use ($termo, $digitos) {
                $query->where(function (Builder $filtro) use ($termo, $digitos) {
                    $filtro->where('name', 'ilike', "%{$termo}%")
                        ->orWhere('email', 'ilike', "%{$termo}%");

                    if ($digitos !== '') {
                        $filtro->orWhere('documento', 'like', "%{$digitos}%");
                    }
                });
            })
            ->orderBy('name')
            ->paginate(20);

        return view('acesso::livewire.clientes.clientes', ['clientes' => $clientes]);
    }
}
