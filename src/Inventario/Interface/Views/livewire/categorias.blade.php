<div class="page">
    <x-ui.breadcrumb :trilha="['Inventário' => null]"/>

    <x-ui.abas :itens="[
        'Obras' => 'inventario.obras',
        'Autores' => 'inventario.autores',
        'Editoras' => 'inventario.editoras',
        'Categorias' => 'inventario.categorias',
    ]"/>

    <div class="page-header">
        <div>
            <h1 class="page-title">Categorias</h1>
            <p class="page-subtitle">Dados de referência usados no cadastro de obras.</p>
        </div>
        <div class="page-actions">
            <label class="input">
                <x-lucide-search class="size-4 opacity-60"/>
                <input type="search" wire:model.live.debounce.400ms="busca" placeholder="Buscar por nome"
                       aria-label="Buscar">
            </label>
        </div>
    </div>

    <x-ui.toast/>

    <div class="painel section">
        <form wire:submit="cadastrar" class="form-inline">
            <x-ui.campo label="Nome" wire:model="nome" autocomplete="off" class="sm:w-80"/>
            <button type="submit" class="btn btn-primary" wire:loading.attr="disabled">
                <x-lucide-plus class="size-4"/>
                Cadastrar
            </button>
        </form>
    </div>

    @if ($registros->isEmpty())
        <x-ui.vazio>Nenhum registro cadastrado.</x-ui.vazio>
    @else
        <div class="table-wrap">
            <table class="table">
                <thead>
                <tr>
                    <th>Nome</th>
                    <th>Cadastrado em</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                @foreach ($registros as $registro)
                    @php($editando = $edicao_id === $registro->id)
                    <tr wire:key="registro-{{ $registro->id }}">
                        <td>
                            @if ($editando)
                                <x-ui.campo wire:model="edicao_nome" wire:keydown.enter="salvar" autocomplete="off"
                                            aria-label="Nome" class="input-sm sm:w-80"/>
                            @else
                                {{ $registro->nome }}
                            @endif
                        </td>
                        <td>{{ $registro->created_at?->format('d/m/Y') }}</td>
                        <td>
                            <div class="table-actions">
                                @if ($editando)
                                    <button type="button" class="btn btn-sm btn-primary" wire:click="salvar"
                                            wire:loading.attr="disabled">
                                        <x-lucide-check class="size-4"/>
                                        Salvar
                                    </button>
                                    <button type="button" class="btn btn-sm btn-ghost" wire:click="cancelarEdicao">
                                        Cancelar
                                    </button>
                                @else
                                    <button type="button" class="btn btn-sm btn-outline"
                                            wire:click="editar({{ $registro->id }})">
                                        <x-lucide-pencil class="size-4"/>
                                        Editar
                                    </button>
                                    <x-ui.confirmacao
                                        titulo="Remover categoria"
                                        texto="A categoria “{{ $registro->nome }}” será removida. Esta ação não poderá ser desfeita."
                                        gatilho="Remover"
                                        rotulo="Remover categoria"
                                        icone="trash-2"
                                        gatilho-classe="btn btn-sm btn-outline btn-error"
                                        wire:click="remover({{ $registro->id }})"
                                    />
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <div class="pagination-wrap">{{ $registros->links() }}</div>
    @endif
</div>
