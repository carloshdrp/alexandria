@php use Emprestimos\Domain\Models\Emprestimo; @endphp
@php use Emprestimos\Domain\Enums\EmprestimoSituacao; @endphp
@php use Emprestimos\Domain\Enums\ReservaSituacao; @endphp
<div class="page">
    <x-ui.breadcrumb :trilha="['Clientes' => route('clientes.index'), $cliente->name => null]"/>

    <div class="page-header">
        <div>
            <h1 class="page-title">{{ $cliente->name }}</h1>
            <p class="page-subtitle">{{ $cliente->email }} ·
                CPF {{ $cliente->documento->formatado() }}@if ($cliente->telefone)
                    · {{ $cliente->telefone }}
                @endif</p>
        </div>
        <div class="page-actions">
            <x-ui.badge :tom="$cliente->situacao->tom()">{{ $cliente->situacao->rotulo() }}</x-ui.badge>
            <a href="{{ route('clientes.editar', $cliente) }}" class="btn btn-sm btn-outline">
                <x-lucide-pencil class="size-4"/>
                Editar cadastro
            </a>
            @if ($cliente->estaBloqueado())
                <button type="button" wire:click="desbloquear" wire:loading.attr="disabled"
                        class="btn btn-sm btn-outline">
                    <x-lucide-user-check class="size-4"/>
                    Desbloquear
                </button>
            @else
                <x-ui.confirmacao
                    titulo="Bloquear cliente"
                    texto="{{ $cliente->name }} não poderá entrar no sistema nem realizar empréstimos até ser desbloqueado."
                    gatilho="Bloquear"
                    rotulo="Bloquear cliente"
                    icone="user-x"
                    gatilho-classe="btn btn-sm btn-outline btn-error"
                    wire:click="bloquear"
                />
            @endif
        </div>
    </div>

    <x-ui.toast/>

    <div class="stat-grid section">
        <div class="stat">
            <div class="stat-label">Empréstimos ativos</div>
            <div class="stat-value">{{ $ativos->count() }}
                / {{ Emprestimo::MAX_ATIVOS_POR_USUARIO }}</div>
        </div>
        <div class="stat">
            <div class="stat-label">Atrasados</div>
            <div
                class="stat-value">{{ $ativos->where('situacao', EmprestimoSituacao::Atrasado)->count() }}</div>
        </div>
        <div class="stat">
            <div class="stat-label">Multas pendentes</div>
            <div class="stat-value">{{ $multasPendentes->count() }}</div>
        </div>
        <div class="stat">
            <div class="stat-label">Reservas ativas</div>
            <div class="stat-value">{{ $reservasAtivas->count() }}</div>
        </div>
    </div>

    <div class="painel section">
        <h2 class="section-title">Emprestar exemplar</h2>
        <form wire:submit="emprestarPorCodigo" class="form-inline">
            <x-ui.campo label="Código de patrimônio" wire:model="codigoPatrimonio" placeholder="EX-000001"
                        autocomplete="off" class="sm:w-64"/>
            <button type="submit" class="btn btn-primary" wire:loading.attr="disabled">
                <x-lucide-book-marked class="size-4"/>
                Emprestar
            </button>
        </form>
    </div>

    <div class="section">
        <h2 class="section-title">Empréstimos ativos</h2>
        @if ($ativos->isEmpty())
            <x-ui.vazio>Nenhum empréstimo em aberto.</x-ui.vazio>
        @else
            <div class="table-wrap">
                <table class="table">
                    <thead>
                    <tr>
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
                    @foreach ($ativos as $emprestimo)
                        @php($exemplar = $exemplares->get($emprestimo->exemplar_id))
                        <tr wire:key="ativo-{{ $emprestimo->id }}">
                            <td>{{ $exemplar?->tituloObra ?? '-' }}</td>
                            <td>{{ $exemplar?->codigoPatrimonio ?? '-' }}</td>
                            <td>{{ $emprestimo->prazo->retiradoEm()->format('d/m/Y') }}</td>
                            <td>{{ $emprestimo->prazo->prazoDevolucao()->format('d/m/Y') }}</td>
                            <td>{{ $emprestimo->qtd_renovacoes }}</td>
                            <td>
                                <x-ui.badge
                                    :tom="$emprestimo->situacao->tom()">{{ $emprestimo->situacao->rotulo() }}</x-ui.badge>
                            </td>
                            <td>
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
                                        texto="O exemplar {{ $exemplar?->codigoPatrimonio }} voltará ao acervo e o empréstimo será encerrado."
                                        gatilho="Devolver"
                                        rotulo="Registrar devolução"
                                        icone="undo-2"
                                        tom="primary"
                                        gatilho-classe="btn btn-sm btn-primary"
                                        wire:click="devolver({{ $emprestimo->id }})"
                                    />
                                </div>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <div class="section">
        <h2 class="section-title">Multas pendentes</h2>
        @if ($multasPendentes->isEmpty())
            <x-ui.vazio>Nenhuma multa pendente.</x-ui.vazio>
        @else
            <div class="table-wrap">
                <table class="table">
                    <thead>
                    <tr>
                        <th>Obra</th>
                        <th>Dias de atraso</th>
                        <th>Valor</th>
                        <th>Gerada em</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach ($multasPendentes as $multa)
                        <tr wire:key="multa-{{ $multa->id }}">
                            <td>{{ $exemplares->get($multa->emprestimo->exemplar_id)?->tituloObra ?? '-' }}</td>
                            <td>{{ $multa->dias_atraso }}</td>
                            <td>{{ $multa->valor }}</td>
                            <td>{{ $multa->created_at?->format('d/m/Y') }}</td>
                            <td>
                                <div class="table-actions">
                                    <x-ui.confirmacao
                                        titulo="Registrar pagamento"
                                        texto="Confirmar o recebimento de {{ $multa->valor }}? A multa será quitada."
                                        gatilho="Registrar pagamento"
                                        rotulo="Confirmar recebimento"
                                        icone="hand-coins"
                                        tom="primary"
                                        gatilho-classe="btn btn-sm btn-primary"
                                        wire:click="registrarPagamento({{ $multa->id }})"
                                    />
                                </div>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <div class="section">
        <h2 class="section-title">Reservas ativas</h2>
        @if ($reservasAtivas->isEmpty())
            <x-ui.vazio>Nenhuma reserva ativa.</x-ui.vazio>
        @else
            <div class="table-wrap">
                <table class="table">
                    <thead>
                    <tr>
                        <th>Obra</th>
                        <th>Reservada em</th>
                        <th>Situação</th>
                        <th>Retirar até</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach ($reservasAtivas as $reserva)
                        <tr wire:key="reserva-{{ $reserva->id }}">
                            <td>{{ $obras->get($reserva->obra_id) }}</td>
                            <td>{{ $reserva->created_at?->format('d/m/Y H:i') }}</td>
                            <td>
                                <x-ui.badge
                                    :tom="$reserva->situacao->tom()">{{ $reserva->situacao->rotulo() }}</x-ui.badge>
                            </td>
                            <td>{{ $reserva->janela?->expiraEm()->format('d/m/Y H:i') ?? '-' }}</td>
                            <td>
                                <div class="table-actions">
                                    @if ($reserva->situacao === ReservaSituacao::Disponivel)
                                        <button type="button" wire:click="atenderReserva({{ $reserva->id }})"
                                                wire:loading.attr="disabled" class="btn btn-sm btn-primary">
                                            <x-lucide-check class="size-4"/>
                                            Atender
                                        </button>
                                    @endif
                                    <x-ui.confirmacao
                                        titulo="Cancelar reserva"
                                        texto="A reserva de “{{ $obras->get($reserva->obra_id) }}” será cancelada e a fila seguirá para o próximo."
                                        gatilho="Cancelar"
                                        rotulo="Cancelar reserva"
                                        icone="x"
                                        gatilho-classe="btn btn-sm btn-outline btn-error"
                                        wire:click="cancelarReserva({{ $reserva->id }})"
                                    />
                                </div>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <div class="section">
        <h2 class="section-title">Histórico de empréstimos</h2>
        @if ($historico->isEmpty())
            <x-ui.vazio>Nenhum empréstimo encerrado.</x-ui.vazio>
        @else
            <div class="table-wrap">
                <table class="table">
                    <thead>
                    <tr>
                        <th>Obra</th>
                        <th>Patrimônio</th>
                        <th>Retirada</th>
                        <th>Encerrado em</th>
                        <th>Situação</th>
                        <th>Renov.</th>
                        <th>Multa</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach ($historico as $emprestimo)
                        @php($exemplar = $exemplares->get($emprestimo->exemplar_id))
                        <tr wire:key="historico-{{ $emprestimo->id }}">
                            <td>{{ $exemplar?->tituloObra ?? '-' }}</td>
                            <td>{{ $exemplar?->codigoPatrimonio ?? '-' }}</td>
                            <td>{{ $emprestimo->prazo->retiradoEm()->format('d/m/Y') }}</td>
                            <td>{{ ($emprestimo->devolvido_em ?? $emprestimo->encerrado_em)?->format('d/m/Y') }}</td>
                            <td>
                                <x-ui.badge
                                    :tom="$emprestimo->situacao->tom()">{{ $emprestimo->situacao->rotulo() }}</x-ui.badge>
                            </td>
                            <td>{{ $emprestimo->qtd_renovacoes }}</td>
                            <td>
                                @if ($emprestimo->multa)
                                    <x-ui.badge
                                        :tom="$emprestimo->multa->situacao->tom()">{{ $emprestimo->multa->valor }}
                                        · {{ $emprestimo->multa->situacao->rotulo() }}</x-ui.badge>
                                @else
                                    -
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
