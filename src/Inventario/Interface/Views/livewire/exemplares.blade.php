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
            <h1 class="page-title">Exemplares</h1>
            <p class="page-subtitle">Exemplares do acervo.</p>
        </div>
        <div class="page-actions">
            <label class="input">
                <x-lucide-search class="size-4 opacity-60"/>
                <input type="search" wire:model.live.debounce.400ms="busca" placeholder="Buscar por codigo"
                       aria-label="Buscar">
            </label>
        </div>
    </div>

    <x-ui.toast/>

    @if ($exemplares->isEmpty())
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
                @foreach ($exemplares as $exemplar)
                    <tr wire:key="obra-{{ $exemplar->id }}">
                        <td>{{ $exemplar->codigo_patrimonio }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <div class="pagination-wrap">{{ $exemplares->links() }}</div>
    @endif
</div>
