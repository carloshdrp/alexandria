<div class="page">
    <x-ui.breadcrumb :trilha="['Minhas multas' => null]"/>

    <div class="page-header">
        <div>
            <h1 class="page-title">Minhas multas</h1>
            <p class="page-subtitle">R$ 1,00 por dia de atraso. O pagamento é feito no balcão da biblioteca.</p>
        </div>
    </div>

    @if ($pendentes->isNotEmpty())
        <x-ui.alerta tipo="error">Você tem multa pendente. Novos empréstimos e renovações ficam bloqueados até a
            quitação.
        </x-ui.alerta>
    @endif

    <div class="section">
        <h2 class="section-title">Pendentes</h2>
        @if ($pendentes->isEmpty())
            <x-ui.vazio>Nenhuma multa pendente.</x-ui.vazio>
        @else
            <div class="table-wrap">
                <table class="table">
                    <thead>
                    <tr>
                        <th>Obra</th>
                        <th>Dias de atraso</th>
                        <th>Valor</th>
                        <th>Gerada em</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach ($pendentes as $multa)
                        <tr wire:key="multa-{{ $multa->id }}">
                            <td>{{ $exemplares->get($multa->emprestimo->exemplar_id)?->tituloObra ?? '-' }}</td>
                            <td>{{ $multa->dias_atraso }}</td>
                            <td>{{ $multa->valor }}</td>
                            <td>{{ $multa->created_at?->format('d/m/Y') }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    @if ($pagas->isNotEmpty())
        <div class="section">
            <h2 class="section-title">Pagas</h2>
            <div class="table-wrap">
                <table class="table">
                    <thead>
                    <tr>
                        <th>Obra</th>
                        <th>Dias de atraso</th>
                        <th>Valor</th>
                        <th>Paga em</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach ($pagas as $multa)
                        <tr wire:key="multa-{{ $multa->id }}">
                            <td>{{ $exemplares->get($multa->emprestimo->exemplar_id)?->tituloObra ?? '-' }}</td>
                            <td>{{ $multa->dias_atraso }}</td>
                            <td>{{ $multa->valor }}</td>
                            <td>{{ $multa->paga_em?->format('d/m/Y') }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
