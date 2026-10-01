@php use Emprestimos\Domain\Enums\ReservaSituacao; @endphp
<div class="page">
    <x-ui.breadcrumb :trilha="['Reservas' => null]"/>

    <div class="page-header">
        <div>
            <h1 class="page-title">Reservas</h1>
            <p class="page-subtitle">Fila por obra em ordem de chegada. Atenda quando o cliente retirar o exemplar.</p>
        </div>
        <label class="input">
            <x-lucide-search class="size-4 opacity-60"/>
            <input type="search" wire:model.live.debounce.400ms="busca" placeholder="Nome, e-mail ou patrimônio"
                   aria-label="Buscar por cliente ou exemplar">
        </label>
    </div>

    <x-ui.toast/>

    @if ($reservas->isEmpty())
        <x-ui.vazio>Nenhuma reserva encontrada.</x-ui.vazio>
    @else
        <div class="table-wrap">
            <table class="table">
                <thead>
                <tr>
                    <th>Obra</th>
                    <th>Cliente</th>
                    <th>Reservada em</th>
                    <th>Situação</th>
                    <th>Retirar até</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                @foreach ($reservas as $reserva)
                    <tr wire:key="reserva-{{ $reserva->id }}">
                        <td><{{ $obras->get($reserva->obra_id) }}</td>
                        <td><a href="{{ route('emprestimos.clientes.situacao', $reserva->user) }}"
                               class="link link-hover">{{ $reserva->user->name }}</a></td>
                        <td>{{ $reserva->created_at?->format('d/m/Y H:i') }}</td>
                        <td>
                            <x-ui.badge :tom="$reserva->situacao->tom()">{{ $reserva->situacao->rotulo() }}</x-ui.badge>
                        </td>
                        <td>{{ $reserva->janela?->expiraEm()->format('d/m/Y H:i') ?? '-' }}</td>
                        <td>
                            <div class="table-actions">
                                @if ($reserva->situacao === ReservaSituacao::Disponivel)
                                    <button type="button" wire:click="atender({{ $reserva->id }})"
                                            wire:loading.attr="disabled" class="btn btn-sm btn-primary">
                                        <x-lucide-check class="size-4"/>
                                        Atender
                                    </button>
                                @endif
                                <x-ui.confirmacao
                                    titulo="Cancelar reserva"
                                    texto="A reserva de {{ $reserva->user->name }} para “{{ $obras->get($reserva->obra_id) }}” será cancelada e a fila seguirá para o próximo."
                                    gatilho="Cancelar"
                                    rotulo="Cancelar reserva"
                                    icone="x"
                                    gatilho-classe="btn btn-sm btn-outline btn-error"
                                    wire:click="cancelar({{ $reserva->id }})"
                                />
                            </div>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <div class="pagination-wrap">{{ $reservas->links() }}</div>
    @endif
</div>
