<div class="page">
    <x-ui.breadcrumb :trilha="['Clientes' => null]"/>

    <div class="page-header">
        <div>
            <h1 class="page-title">Clientes</h1>
            <p class="page-subtitle">Consulte a situação de um leitor ou cadastre um novo no balcão.</p>
        </div>
        <div class="page-actions">
            <x-ui.campo type="select" wire:model.live="situacao" aria-label="Situação" class="sm:w-48">
                @foreach ($situacoes as $opcao)
                    <option value="{{ $opcao->value }}">{{ $opcao->rotulo() }}</option>
                @endforeach
            </x-ui.campo>
            <label class="input">
                <x-lucide-search class="size-4 opacity-60"/>
                <input type="search" wire:model.live.debounce.400ms="busca" placeholder="Nome, e-mail ou CPF"
                       aria-label="Buscar">
            </label>
            <a href="{{ route('clientes.novo') }}" class="btn btn-primary">
                <x-lucide-user-plus class="size-4"/>
                Novo cliente
            </a>
        </div>
    </div>

    <x-ui.toast/>

    @if ($clientes->isEmpty())
        <x-ui.vazio>Nenhum cliente encontrado.</x-ui.vazio>
    @else
        <div class="table-wrap">
            <table class="table">
                <thead>
                <tr>
                    <th>Nome</th>
                    <th>E-mail</th>
                    <th>CPF</th>
                    <th>Situação</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                @foreach ($clientes as $cliente)
                    <tr wire:key="cliente-{{ $cliente->id }}">
                        <td>{{ $cliente->name }}</td>
                        <td>{{ $cliente->email }}</td>
                        <td>{{ $cliente->documento->formatado() }}</td>
                        <td>
                            <x-ui.badge :tom="$cliente->situacao->tom()">{{ $cliente->situacao->rotulo() }}</x-ui.badge>
                        </td>
                        <td>
                            <div class="table-actions">
                                <a href="{{ route('emprestimos.clientes.situacao', $cliente) }}"
                                   class="btn btn-sm btn-outline">
                                    <x-lucide-user-round-search class="size-4"/>
                                    Situação
                                </a>
                                <a href="{{ route('clientes.editar', $cliente) }}" class="btn btn-sm btn-outline">
                                    <x-lucide-pencil class="size-4"/>
                                    Editar
                                </a>
                            </div>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <div class="pagination-wrap">{{ $clientes->links() }}</div>
    @endif
</div>
