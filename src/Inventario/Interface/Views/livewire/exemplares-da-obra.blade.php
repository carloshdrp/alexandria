@php use Inventario\Domain\Enums\ExemplarSituacao; @endphp
<div class="page">
    <x-ui.breadcrumb :trilha="['Inventário' => route('inventario.obras'), $obra->titulo => null]"/>

    <div class="page-header">
        <div>
            <h1 class="page-title">{{ $obra->titulo }}</h1>
            <p class="page-subtitle">Exemplares físicos desta obra.</p>
        </div>
        <div class="page-actions">
            <a href="{{ route('emprestimos.catalogo.obra', $obra->id) }}" class="btn btn-outline">
                <x-lucide-book-open class="size-4"/>
                Ver no catálogo
            </a>
            <a href="{{ route('inventario.obras.editar', $obra) }}" class="btn btn-outline">
                <x-lucide-pencil class="size-4"/>
                Editar obra
            </a>
        </div>
    </div>

    <x-ui.toast/>

    <div class="painel section">
        <h2 class="section-title">Cadastrar exemplar</h2>
        <form wire:submit="cadastrar" class="form-inline">
            <x-ui.campo label="Código de patrimônio" wire:model="codigo_patrimonio" placeholder="EX-000001"
                        autocomplete="off" class="sm:w-64"/>
            <x-ui.campo label="Conservação" type="select" wire:model="estado_conservacao" class="sm:w-56">
                <option value="">Selecione…</option>
                @foreach ($estados as $estado)
                    <option value="{{ $estado->value }}">{{ $estado->rotulo() }}</option>
                @endforeach
            </x-ui.campo>
            <button type="submit" class="btn btn-primary" wire:loading.attr="disabled">
                <x-lucide-plus class="size-4"/>
                Cadastrar
            </button>
        </form>
    </div>

    @if ($exemplares->isEmpty())
        <x-ui.vazio>Nenhum exemplar cadastrado.</x-ui.vazio>
    @else
        <div class="table-wrap">
            <table class="table">
                <thead>
                <tr>
                    <th>Patrimônio</th>
                    <th>Conservação</th>
                    <th>Situação</th>
                    <th>Baixa</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                @foreach ($exemplares as $exemplar)
                    @php($baixado = $exemplar->situacao === ExemplarSituacao::Baixado)
                    <tr wire:key="exemplar-{{ $exemplar->id }}">
                        <td>{{ $exemplar->codigo_patrimonio }}</td>
                        <td>
                            @if ($baixado)
                                {{ $exemplar->estado_conservacao->rotulo() }}
                            @else
                                <select class="select select-sm" aria-label="Conservação"
                                        wire:change="alterarConservacao({{ $exemplar->id }}, $event.target.value)">
                                    @foreach ($estados as $estado)
                                        <option
                                            value="{{ $estado->value }}" @selected($exemplar->estado_conservacao === $estado)>{{ $estado->rotulo() }}</option>
                                    @endforeach
                                </select>
                            @endif
                        </td>
                        <td>
                            <x-ui.badge
                                :tom="$exemplar->situacao->tom()">{{ $exemplar->situacao->rotulo() }}</x-ui.badge>
                        </td>
                        <td>
                            @if ($baixado)
                                {{ $exemplar->motivo_baixa?->rotulo() }}
                                em {{ $exemplar->baixado_em?->format('d/m/Y') }}
                            @else
                                -
                            @endif
                        </td>
                        <td>
                            @unless ($baixado)
                                <div class="table-actions">
                                    @foreach ($motivos as $motivo)
                                        <x-ui.confirmacao
                                            :key="'baixa-'.$exemplar->id.'-'.$motivo->value"
                                            titulo="Dar baixa em exemplar"
                                            texto="O exemplar {{ $exemplar->codigo_patrimonio }} será baixado como {{ strtolower($motivo->rotulo()) }} e não poderá mais ser emprestado."
                                            gatilho="{{ $motivo->rotulo() }}"
                                            rotulo="Dar baixa"
                                            icone="package-x"
                                            gatilho-classe="btn btn-sm btn-outline btn-error"
                                            wire:click="baixar({{ $exemplar->id }}, {{ $motivo->value }})"
                                        />
                                    @endforeach
                                </div>
                            @endunless
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
