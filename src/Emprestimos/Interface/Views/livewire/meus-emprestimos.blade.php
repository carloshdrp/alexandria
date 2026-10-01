@php use Emprestimos\Domain\Models\Emprestimo; @endphp
<div class="page">
    <x-ui.breadcrumb :trilha="['Meus empréstimos' => null]"/>

    <div class="page-header">
        <div>
            <h1 class="page-title">Meus empréstimos</h1>
            <p class="page-subtitle">Até {{ Emprestimo::MAX_ATIVOS_POR_USUARIO }} empréstimos
                ativos, {{ Emprestimo::MAX_RENOVACOES }} renovações cada.</p>
        </div>
    </div>

    <x-ui.toast/>

    <div class="tabs tabs-lift mb-4">
        <button type="button" wire:click="$set('aba', 'ativos')" @class(['tab', 'tab-active' => $aba === 'ativos'])>
            Ativos
        </button>
        <button type="button"
                wire:click="$set('aba', 'historico')" @class(['tab', 'tab-active' => $aba === 'historico'])>Histórico
        </button>
    </div>

    @if ($emprestimos->isEmpty())
        <x-ui.vazio>{{ $aba === 'ativos' ? 'Você não tem empréstimos em aberto.' : 'Nenhum empréstimo encerrado ainda.' }}</x-ui.vazio>
    @else
        <div class="table-wrap">
            <table class="table">
                <thead>
                <tr>
                    <th>Obra</th>
                    <th>Patrimônio</th>
                    <th>Retirada</th>
                    <th>{{ $aba === 'ativos' ? 'Devolver até' : 'Encerrado em' }}</th>
                    <th>Renovações</th>
                    <th>Situação</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                @foreach ($emprestimos as $emprestimo)
                    @php($exemplar = $exemplares->get($emprestimo->exemplar_id))
                    <tr wire:key="emprestimo-{{ $emprestimo->id }}">
                        <td>{{ $exemplar?->tituloObra ?? '-' }}</td>
                        <td>{{ $exemplar?->codigoPatrimonio ?? '-' }}</td>
                        <td>{{ $emprestimo->prazo->retiradoEm()->format('d/m/Y') }}</td>
                        <td>{{ $aba === 'ativos' ? $emprestimo->prazo->prazoDevolucao()->format('d/m/Y') : ($emprestimo->devolvido_em ?? $emprestimo->encerrado_em)?->format('d/m/Y') }}</td>
                        <td>{{ $emprestimo->qtd_renovacoes }}</td>
                        <td>
                            <x-ui.badge
                                :tom="$emprestimo->situacao->tom()">{{ $emprestimo->situacao->rotulo() }}</x-ui.badge>
                            @if ($emprestimo->multa)
                                <x-ui.badge :tom="$emprestimo->multa->situacao->tom()">
                                    Multa {{ $emprestimo->multa->valor }}</x-ui.badge>
                            @endif
                        </td>
                        <td>
                            @if ($aba === 'ativos')
                                <div class="table-actions">
                                    @if ($emprestimo->prazo->estaNaJanelaDeRenovacao())
                                        <button type="button" wire:click="renovar({{ $emprestimo->id }})"
                                                wire:loading.attr="disabled" class="btn btn-sm btn-outline">
                                            <x-lucide-refresh-cw class="size-4"/>
                                            Renovar
                                        </button>
                                    @endif
                                </div>
                            @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
