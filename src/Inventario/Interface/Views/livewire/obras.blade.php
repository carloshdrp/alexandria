<div class="page">
    <x-ui.breadcrumb :trilha="['Inventário' => null]"/>

    <x-ui.abas :itens="[
        'Obras' => 'inventario.obras',
        'Exemplares' => 'inventario.exemplares',
        'Autores' => 'inventario.autores',
        'Editoras' => 'inventario.editoras',
        'Categorias' => 'inventario.categorias',
    ]"/>

    <div class="page-header">
        <div>
            <h1 class="page-title">Obras</h1>
            <p class="page-subtitle">Cadastro bibliográfico do acervo.</p>
        </div>
        <div class="page-actions">
            <a href="{{ route('inventario.obras.nova') }}" class="btn btn-primary">
                <x-lucide-plus class="size-4"/>
                Nova obra
            </a>
            <label class="input">
                <x-lucide-search class="size-4 opacity-60"/>
                <input type="search" wire:model.live.debounce.400ms="busca" placeholder="Buscar por título"
                       aria-label="Buscar">
            </label>
        </div>
    </div>

    <x-ui.toast/>

    @if ($obras->isEmpty())
        <x-ui.vazio>Nenhuma obra cadastrada.</x-ui.vazio>
    @else
        <div class="table-wrap">
            <table class="table">
                <thead>
                <tr>
                    <th>Título</th>
                    <th>Autores</th>
                    <th>Editora</th>
                    <th>Categoria</th>
                    <th>Ano</th>
                    <th>Exemplares</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                @foreach ($obras as $obra)
                    <tr wire:key="obra-{{ $obra->id }}">
                        <td>{{ $obra->titulo }}</td>
                        <td>{{ $obra->autores->pluck('nome')->implode(', ') }}</td>
                        <td>{{ $obra->editora->nome }}</td>
                        <td>{{ $obra->categoria->nome }}</td>
                        <td>{{ $obra->ano_publicacao ?? '-' }}</td>
                        <td>{{ $obra->exemplares_disponiveis }} / {{ $obra->exemplares_total }}</td>
                        <td>
                            <div class="table-actions">
                                <a href="{{ route('inventario.obras.exemplares', $obra) }}"
                                   class="btn btn-sm btn-outline">
                                    <x-lucide-layers class="size-4"/>
                                    Exemplares
                                </a>
                                <a href="{{ route('inventario.obras.editar', $obra) }}" class="btn btn-sm btn-outline">
                                    <x-lucide-pencil class="size-4"/>
                                    Editar
                                </a>
                                <x-ui.confirmacao
                                    titulo="Remover obra"
                                    texto="A obra “{{ $obra->titulo }}” será removida do acervo. Esta ação não poderá ser desfeita."
                                    gatilho="Remover"
                                    rotulo="Remover obra"
                                    icone="trash-2"
                                    gatilho-classe="btn btn-sm btn-outline btn-error"
                                    wire:click="remover({{ $obra->id }})"
                                />
                            </div>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <div class="pagination-wrap">{{ $obras->links() }}</div>
    @endif
</div>
