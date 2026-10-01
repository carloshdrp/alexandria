@php
    $sucesso = session()->pull('sucesso');
    $erro = $errors->first('dominio');
    $chave = Str::random(8);
@endphp

@if ($sucesso || $erro)
    <div class="toast toast-top toast-end z-50 print:hidden">
        @if ($sucesso)
            <div wire:key="toast-sucesso-{{ $chave }}"
                 x-data="{ visivel: true }"
                 x-show="visivel"
                 x-init="setTimeout(() => visivel = false, 5000)"
                 x-transition.duration.300ms
            >
                <x-ui.alerta tipo="success" class="shadow-lg">
                    {{ $sucesso }}

                    <x-slot:acao>
                        <button type="button"
                                class="btn btn-ghost btn-xs btn-circle"
                                aria-label="Fechar"
                                @click="visivel = false"
                        >
                            <x-lucide-x class="size-4"/>
                        </button>
                    </x-slot:acao>
                </x-ui.alerta>
            </div>
        @endif

        @if ($erro)
            <div wire:key="toast-erro-{{ $chave }}"
                 x-data="{ visivel: true }"
                 x-show="visivel"
                 x-init="setTimeout(() => visivel = false, 8000)"
                 x-transition.duration.300ms
            >
                <x-ui.alerta tipo="error" class="shadow-lg">
                    {{ $erro }}

                    <x-slot:acao>
                        <button type="button"
                                class="btn btn-ghost btn-xs btn-circle"
                                aria-label="Fechar"
                                @click="visivel = false"
                        >
                            <x-lucide-x class="size-4"/>
                        </button>
                    </x-slot:acao>
                </x-ui.alerta>
            </div>
        @endif
    </div>
@endif
