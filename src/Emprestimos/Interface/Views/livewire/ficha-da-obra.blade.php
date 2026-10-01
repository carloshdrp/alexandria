<div class="page">
    <x-ui.breadcrumb :trilha="['Catálogo' => route('emprestimos.catalogo'), $obra->titulo => null]"/>

    <div class="page-header">
        <div>
            <h1 class="page-title">{{ $obra->titulo }}</h1>
            <p class="page-subtitle">{{ $obra->autoresFormatados() }}</p>
        </div>
        <div class="page-actions">
            @cannot('bibliotecario')
                @if ($jaEmprestado)
                    <x-ui.badge tom="info">Você já está com um exemplar desta obra</x-ui.badge>
                @elseif ($jaReservou)
                    <x-ui.badge tom="info">Você já tem uma reserva desta obra</x-ui.badge>
                @elseif ($obra->exemplaresTotal === 0)
                    <x-ui.badge tom="neutral">Sem exemplares no acervo</x-ui.badge>
                @elseif ($obra->temExemplarDisponivel())
                    <x-ui.badge tom="success">Disponível para retirada no balcão</x-ui.badge>
                @else
                    <button type="button" wire:click="reservar" wire:loading.attr="disabled" class="btn btn-primary">
                        <x-lucide-bookmark class="size-4"/>
                        Reservar
                    </button>
                @endif
            @endcannot
        </div>
    </div>

    <x-ui.toast/>

    <div class="grid gap-6 lg:grid-cols-[16rem_1fr]">
        <div>
            <x-ui.capa :url="$obra->capaUrl" :titulo="$obra->titulo" prioritaria class="rounded-lg"/>
        </div>

        <div class="space-y-6">
            <div class="painel">
                <dl class="detail-list">
                    <dt>Editora</dt>
                    <dd>{{ $obra->editora }}</dd>
                    <dt>Categoria</dt>
                    <dd>{{ $obra->categoria }}</dd>
                    <dt>Ano</dt>
                    <dd>{{ $obra->anoPublicacao ?? '-' }}</dd>
                    <dt>ISBN</dt>
                    <dd>{{ $obra->isbn ?? '-' }}</dd>
                    <dt>Disponibilidade</dt>
                    <dd>{{ $obra->exemplaresDisponiveis }} de {{ $obra->exemplaresTotal }} exemplares disponíveis</dd>
                    <dt>Fila de reservas</dt>
                    <dd>{{ $tamanhoDaFila }} {{ $tamanhoDaFila === 1 ? 'pessoa' : 'pessoas' }} aguardando</dd>
                </dl>
            </div>

            @can('bibliotecario')
                @if ($obra->temExemplarDisponivel())
                    <div class="painel">
                        <h2 class="section-title">Emprestar no balcão</h2>
                        <form wire:submit="emprestar" class="form-inline">
                            <x-ui.campo label="Exemplar" type="select" wire:model="exemplarId" class="sm:w-72">
                                <option value="">Selecione…</option>
                                @foreach ($exemplares->where('disponivel', true) as $exemplar)
                                    <option value="{{ $exemplar->id }}">{{ $exemplar->codigoPatrimonio }}
                                        · {{ $exemplar->estadoConservacao }}</option>
                                @endforeach
                            </x-ui.campo>
                            <x-ui.campo label="Cliente (e-mail ou CPF)" wire:model="cliente"
                                        placeholder="cliente@exemplo.com" class="sm:w-72"/>
                            <button type="submit" class="btn btn-primary" wire:loading.attr="disabled">
                                <x-lucide-book-marked class="size-4"/>
                                Emprestar
                            </button>
                        </form>
                    </div>
                @elseif ($obra->exemplaresTotal === 0)
                    <div class="painel">
                        <x-ui.badge tom="neutral">Sem exemplares no acervo</x-ui.badge>
                    </div>
                @else
                    <div class="painel">
                        <h2 class="section-title">Reservar para um cliente</h2>
                        <p class="form-hint mb-3">Nenhum exemplar disponível. A reserva entra na fila e o cliente é
                            avisado por e-mail quando um exemplar voltar.</p>
                        <form wire:submit="reservarParaCliente" class="form-inline">
                            <x-ui.campo label="Cliente (e-mail ou CPF)" wire:model="cliente"
                                        placeholder="cliente@exemplo.com" class="sm:w-72"/>
                            <button type="submit" class="btn btn-primary" wire:loading.attr="disabled">
                                <x-lucide-bookmark class="size-4"/>
                                Reservar
                            </button>
                        </form>
                    </div>
                @endif
            @endcan

            <div class="section">
                <h2 class="section-title">Exemplares</h2>
                @if ($exemplares->isEmpty())
                    <x-ui.vazio>Esta obra ainda não possui exemplares.</x-ui.vazio>
                @else
                    <div class="table-wrap">
                        <table class="table">
                            <thead>
                            <tr>
                                <th>Patrimônio</th>
                                <th>Conservação</th>
                                <th>Situação</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach ($exemplares as $exemplar)
                                <tr wire:key="exemplar-{{ $exemplar->id }}">
                                    <td>{{ $exemplar->codigoPatrimonio }}</td>
                                    <td>{{ $exemplar->estadoConservacao }}</td>
                                    <td>
                                        <x-ui.badge
                                            :tom="$exemplar->disponivel ? 'success' : ($exemplar->reservado ? 'warning' : 'neutral')">{{ $exemplar->situacao }}</x-ui.badge>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
