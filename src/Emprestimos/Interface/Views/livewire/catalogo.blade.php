<div class="page">
    <x-ui.breadcrumb :trilha="['Catálogo' => null]"/>

    <div class="page-header">
        <div>
            <h1 class="page-title">Catálogo</h1>
            <p class="page-subtitle">Busque por título ou autor e veja a disponibilidade de cada obra.</p>
        </div>
        <label class="input">
            <x-lucide-search class="size-4 opacity-60"/>
            <input type="search" wire:model.live.debounce.400ms="busca" placeholder="Buscar obra ou autor…"
                   aria-label="Buscar">
        </label>
    </div>

    <x-ui.toast/>

    <div wire:loading.delay class="loading-hint">Buscando…</div>

    @if ($obras->isEmpty())
        <x-ui.vazio>Nenhuma obra encontrada.</x-ui.vazio>
    @else
        <div class="painel-grid">
            @foreach ($obras as $obra)
                <a href="{{ route('emprestimos.catalogo.obra', $obra->id) }}" class="obra-card"
                   wire:key="obra-{{ $obra->id }}">
                    <x-ui.capa :url="$obra->capaMiniaturaUrl" :fallback="$obra->capaUrl" :titulo="$obra->titulo"
                               :prioritaria="$loop->index < 4"/>
                    <div class="obra-body">
                        <h2 class="obra-titulo">{{ $obra->titulo }}</h2>
                        <p class="obra-meta">{{ $obra->autoresFormatados() }}</p>
                        <p class="obra-meta">{{ $obra->categoria }}@if ($obra->anoPublicacao)
                                · {{ $obra->anoPublicacao }}
                            @endif</p>
                        <p class="obra-disponibilidade">
                            @if ($obra->exemplaresTotal === 0)
                                <x-ui.badge tom="neutral">Sem exemplares</x-ui.badge>
                            @elseif ($obra->temExemplarDisponivel())
                                <x-ui.badge tom="success">{{ $obra->exemplaresDisponiveis }}
                                    de {{ $obra->exemplaresTotal }} disponíveis
                                </x-ui.badge>
                            @else
                                <x-ui.badge tom="warning">Todos emprestados · reservável</x-ui.badge>
                            @endif
                        </p>
                    </div>
                </a>
            @endforeach
        </div>

        <div class="pagination-wrap">{{ $obras->links() }}</div>
    @endif
</div>
