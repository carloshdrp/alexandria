@php use Emprestimos\Domain\Enums\EmprestimoSituacao; @endphp
<div class="page">
    <x-ui.breadcrumb :trilha="['Empréstimos' => null]"/>

    <div class="page-header">
        <div>
            <h1 class="page-title">Empréstimos</h1>
            <p class="page-subtitle">Registre devoluções e renovações no balcão.</p>
        </div>
        <div class="form-inline">
            <x-ui.campo type="select" wire:model.live="situacao" aria-label="Situação" class="sm:w-48">
                <option value="">Em aberto</option>
                @foreach ($situacoes as $opcao)
                    <option value="{{ $opcao->value }}">{{ $opcao->rotulo() }}</option>
                @endforeach
            </x-ui.campo>
            <label class="input">
                <x-lucide-search class="size-4 opacity-60"/>
                <input type="search" wire:model.live.debounce.400ms="busca" placeholder="Nome, e-mail ou patrimônio"
                       aria-label="Buscar por cliente ou exemplar">
            </label>
        </div>
    </div>

    <x-ui.toast/>

    @if ($emprestimos->isEmpty())
        <x-ui.vazio>Nenhum empréstimo encontrado.</x-ui.vazio>
    @else
        <div class="table-wrap">
            <table class="table">
                <thead>
                <tr>
                    <th>Cliente</th>
                    <th>Obra</th>
                    <th>Patrimônio</th>
                    <th>Retirada</th>
                    <th>Devolver até</th>
                    <th>Renov.</th>
                    <th>Situação</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                @foreach ($emprestimos as $emprestimo)
                    @php($exemplar = $exemplares->get($emprestimo->exemplar_id))
                    <tr wire:key="emprestimo-{{ $emprestimo->id }}">
                        <td>
                            <a href="{{ route('emprestimos.clientes.situacao', $emprestimo->user) }}"
                               class="link link-hover">{{ $emprestimo->user->name }}</a>
                        </td>
                        <td>
                            <a href="{{ route('inventario.obras', ['busca'=>$exemplar->tituloObra])}}"
                               class="link link-hover">{{ $exemplar?->tituloObra ?? '-' }}</a>
                        </td>
                        <td>{{ $exemplar?->codigoPatrimonio ?? '-' }}</td>
                        <td>{{ $emprestimo->prazo->retiradoEm()->format('d/m/Y') }}</td>
                        <td>{{ $emprestimo->prazo->prazoDevolucao()->format('d/m/Y') }}</td>
                        <td>{{ $emprestimo->qtd_renovacoes }}</td>
                        <td>
                            <x-ui.badge
                                :tom="$emprestimo->situacao->tom()">{{ $emprestimo->situacao->rotulo() }}</x-ui.badge>
                        </td>
                        <td>
                            @if (in_array($emprestimo->situacao, [EmprestimoSituacao::Andamento, EmprestimoSituacao::Atrasado], true))
                                <div class="table-actions">
                                    @if ($emprestimo->prazo->estaNaJanelaDeRenovacao())
                                        <button type="button" wire:click="renovar({{ $emprestimo->id }})"
                                                wire:loading.attr="disabled" class="btn btn-sm btn-outline">
                                            <x-lucide-refresh-cw class="size-4"/>
                                            Renovar
                                        </button>
                                    @endif
                                    <x-ui.confirmacao
                                        titulo="Registrar devolução"
                                        texto="O exemplar {{ $exemplar?->codigoPatrimonio }} voltará ao acervo e o empréstimo de {{ $emprestimo->user->name }} será encerrado."
                                        gatilho="Devolver"
                                        rotulo="Registrar devolução"
                                        icone="undo-2"
                                        tom="primary"
                                        gatilho-classe="btn btn-sm btn-primary"
                                        wire:click="devolver({{ $emprestimo->id }})"
                                    />
                                </div>
                            @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <div class="pagination-wrap">{{ $emprestimos->links() }}</div>
    @endif
</div>
