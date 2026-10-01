@props([
    'titulo' => 'Confirmar ação',
    'texto' => 'Esta ação não poderá ser desfeita.',
    'rotulo' => 'Confirmar',
    'gatilho' => 'Confirmar',
    'icone' => null,
    'tom' => 'error',
    'gatilhoClasse' => 'btn btn-sm btn-outline',
])

<div x-data class="inline-flex">
    <button type="button" class="{{ $gatilhoClasse }}" @click="$refs.dialogo.showModal()">
        @if ($icone)
            <x-dynamic-component :component="'lucide-'.$icone" class="size-4"/>
        @endif
        {{ $gatilho }}
    </button>

    <dialog x-ref="dialogo" class="modal">
        <div class="modal-box">
            <h3 class="text-lg font-semibold">{{ $titulo }}</h3>
            <p class="py-4 text-sm text-stone-600">{{ $texto }}</p>
            <div class="modal-action">
                <form method="dialog">
                    <button class="btn btn-ghost">Voltar</button>
                </form>
                <button type="button"
                        class="btn btn-{{ $tom }}"
                        @click="$refs.dialogo.close()"
                    {{ $attributes }}
                >{{ $rotulo }}</button>
            </div>
        </div>
        <form method="dialog" class="modal-backdrop">
            <button aria-label="Fechar">fechar</button>
        </form>
    </dialog>
</div>
