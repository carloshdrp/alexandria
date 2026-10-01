@php use Emprestimos\Domain\Enums\ReservaSituacao; @endphp
<div class="page">
    <x-ui.breadcrumb :trilha="['Minhas reservas' => null]"/>

    <div class="page-header">
        <div>
            <h1 class="page-title">Minhas reservas</h1>
            <p class="page-subtitle">Quando um exemplar ficar disponível você terá 48 horas para retirá-lo no
                balcão.</p>
        </div>
    </div>

    <x-ui.toast/>

    <div class="section">
        <h2 class="section-title">Ativas</h2>
        @if ($ativas->isEmpty())
            <x-ui.vazio>Nenhuma reserva ativa. Reserve uma obra pelo <a href="{{ route('emprestimos.catalogo') }}"
                                                                        class="link link-hover">catálogo</a> quando não
                houver exemplar disponível.
            </x-ui.vazio>
        @else
            <div class="table-wrap">
                <table class="table">
                    <thead>
                    <tr>
                        <th>Obra</th>
                        <th>Reservada em</th>
                        <th>Situação</th>
                        <th>Posição / prazo</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach ($ativas as $reserva)
                        <tr wire:key="reserva-{{ $reserva->id }}">
                            <td>{{ $obras->get($reserva->obra_id) }}</td>
                            <td>{{ $reserva->created_at?->format('d/m/Y H:i') }}</td>
                            <td>
                                <x-ui.badge
                                    :tom="$reserva->situacao->tom()">{{ $reserva->situacao->rotulo() }}</x-ui.badge>
                            </td>
                            <td>
                                @if ($reserva->situacao === ReservaSituacao::Disponivel)
                                    Retirar até {{ $reserva->janela?->expiraEm()->format('d/m/Y H:i') }}
                                @else
                                    {{ $posicoes->get($reserva->id) }}º na fila
                                @endif
                            </td>
                            <td>
                                <div class="table-actions">
                                    <x-ui.confirmacao
                                        titulo="Cancelar reserva"
                                        texto="Sua reserva de “{{ $obras->get($reserva->obra_id) }}” será cancelada e você perderá a posição na fila."
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
        @endif
    </div>

    @if ($encerradas->isNotEmpty())
        <div class="section">
            <h2 class="section-title">Histórico</h2>
            <div class="table-wrap">
                <table class="table">
                    <thead>
                    <tr>
                        <th>Obra</th>
                        <th>Reservada em</th>
                        <th>Situação</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach ($encerradas as $reserva)
                        <tr wire:key="reserva-{{ $reserva->id }}">
                            <td>{{ $obras->get($reserva->obra_id) }}</td>
                            <td>{{ $reserva->created_at?->format('d/m/Y H:i') }}</td>
                            <td>
                                <x-ui.badge
                                    :tom="$reserva->situacao->tom()">{{ $reserva->situacao->rotulo() }}</x-ui.badge>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
