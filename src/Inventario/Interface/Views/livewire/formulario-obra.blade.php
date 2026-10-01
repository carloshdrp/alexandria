<div class="page">
    <x-ui.breadcrumb
        :trilha="['Inventário' => route('inventario.obras'), ($obra ? 'Editar obra' : 'Nova obra') => null]"/>

    <div class="page-header">
        <div>
            <h1 class="page-title">{{ $obra ? 'Editar obra' : 'Nova obra' }}</h1>
        </div>
    </div>

    <x-ui.toast/>

    <form wire:submit="salvar" class="painel form">
        <x-ui.campo label="Título" wire:model="titulo" required/>

        <div class="form-row">
            <x-ui.campo label="ISBN" wire:model="isbn" placeholder="Opcional"/>
            <x-ui.campo label="Ano de publicação" type="number" wire:model="ano_publicacao" min="1450"
                        max="{{ date('Y') }}" placeholder="Opcional"/>
        </div>

        <div class="form-row">
            <x-ui.campo label="Editora" type="select" wire:model="editora_id">
                <option value="">Selecione…</option>
                @foreach ($editoras as $editora)
                    <option value="{{ $editora->id }}">{{ $editora->nome }}</option>
                @endforeach
            </x-ui.campo>
            <x-ui.campo label="Categoria" type="select" wire:model="categoria_id">
                <option value="">Selecione…</option>
                @foreach ($categorias as $categoria)
                    <option value="{{ $categoria->id }}">{{ $categoria->nome }}</option>
                @endforeach
            </x-ui.campo>
        </div>

        <div class="form-field">
            <span class="form-label">Autores</span>

            @if ($autoresSelecionados->isEmpty())
                <p class="form-hint">Nenhum autor adicionado.</p>
            @else
                <ol class="autoria">
                    @foreach ($autoresSelecionados as $posicao => $autor)
                        <li class="autoria-item" wire:key="autor-{{ $autor->id }}">
                            <span class="autoria-ordem">{{ $posicao + 1 }}</span>
                            <span class="autoria-nome">{{ $autor->nome }}</span>
                            <div class="table-actions">
                                <button type="button" class="btn btn-sm btn-ghost"
                                        aria-label="Mover {{ $autor->nome }} para cima"
                                        wire:click="moverAutor({{ $posicao }}, {{ $posicao - 1 }})"
                                    @disabled($loop->first)>
                                    <x-lucide-arrow-up class="size-4"/>
                                </button>
                                <button type="button" class="btn btn-sm btn-ghost"
                                        aria-label="Mover {{ $autor->nome }} para baixo"
                                        wire:click="moverAutor({{ $posicao }}, {{ $posicao + 1 }})"
                                    @disabled($loop->last)>
                                    <x-lucide-arrow-down class="size-4"/>
                                </button>
                                <button type="button" class="btn btn-sm btn-ghost btn-error"
                                        aria-label="Remover {{ $autor->nome }}"
                                        wire:click="removerAutor({{ $autor->id }})">
                                    <x-lucide-x class="size-4"/>
                                </button>
                            </div>
                        </li>
                    @endforeach
                </ol>
            @endif

            <div class="form-inline">
                <x-ui.campo label="Adicionar autor" type="select" wire:model="autor_id" class="sm:w-80">
                    <option value="">Selecione…</option>
                    @foreach ($todosAutores as $autor)
                        @continue(in_array($autor->id, $autores, true))
                        <option value="{{ $autor->id }}">{{ $autor->nome }}</option>
                    @endforeach
                </x-ui.campo>
                <button type="button" class="btn btn-outline" wire:click="adicionarAutor">
                    <x-lucide-plus class="size-4"/>
                    Adicionar
                </button>
            </div>

            @error('autores') <span class="form-error">{{ $message }}</span> @enderror
            @error('autores.*') <span class="form-error">{{ $message }}</span> @enderror
        </div>

        <div class="form-field">
            <label for="capa" class="form-label">Capa</label>
            <input id="capa" type="file" wire:model="capa" class="file-input w-full"
                   accept="image/jpeg,image/png,image/webp">
            <span class="form-hint">JPEG, PNG ou WebP até 5 MB.</span>
            <div wire:loading wire:target="capa" class="loading-hint">Enviando capa…</div>
            @error('capa') <span class="form-error">{{ $message }}</span> @enderror
            @if ($capa)
                <img src="{{ $capa->temporaryUrl() }}" alt="Prévia da capa" class="mt-2 h-72 mx-auto w-fit rounded">
            @elseif ($obra?->capa_url)
                <img src="{{ $obra->capa_url }}" alt="Capa atual" class="mt-2 h-72 mx-auto w-fit rounded">
            @endif
        </div>

        <div class="form-actions">
            <a href="{{ route('inventario.obras') }}" class="btn btn-outline">Cancelar</a>
            <button type="submit" class="btn btn-primary" wire:loading.attr="disabled" wire:target="salvar">
                <x-lucide-save class="size-4"/>
                Salvar
            </button>
        </div>
    </form>
</div>
