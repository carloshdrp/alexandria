<div class="page">
    <x-ui.breadcrumb :trilha="['Multas' => null]"/>

    <div class="page-header">
        <div>
            <h1 class="page-title">Multas pendentes</h1>
            <p class="page-subtitle">Registre o pagamento recebido no balcão.</p>
        </div>
    </div>

    <x-ui.toast/>

    @if ($multas->isEmpty())
        <x-ui.vazio>Nenhuma multa pendente.</x-ui.vazio>
    @else
        <div class="table-wrap">
            <table class="table">
                <thead>
                <tr>
                    <th>Cliente</th>
                    <th>Obra</th>
                    <th>Dias de atraso</th>
                    <th>Valor</th>
                    <th>Gerada em</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                @foreach ($multas as $multa)
                    <tr wire:key="multa-{{ $multa->id }}">
                        <td><a href="{{ route('emprestimos.clientes.situacao', $multa->user) }}"
                               class="link link-hover">{{ $multa->user->name }}</a></td>
                        <td>{{ $exemplares->get($multa->emprestimo->exemplar_id)?->tituloObra ?? '-' }}</td>
                        <td>{{ $multa->dias_atraso }}</td>
                        <td>{{ $multa->valor }}</td>
                        <td>{{ $multa->created_at?->format('d/m/Y') }}</td>
                        <td>
                            <div class="table-actions">
                                <x-ui.confirmacao
                                    titulo="Registrar pagamento"
                                    texto="Confirmar o recebimento de {{ $multa->valor }} de {{ $multa->user->name }}? A multa será quitada."
                                    gatilho="Registrar pagamento"
                                    rotulo="Confirmar recebimento"
                                    icone="hand-coins"
                                    tom="primary"
                                    gatilho-classe="btn btn-sm btn-primary"
                                    wire:click="registrarPagamento({{ $multa->id }})"
                                />
                            </div>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <div class="pagination-wrap">{{ $multas->links() }}</div>
    @endif
</div>
