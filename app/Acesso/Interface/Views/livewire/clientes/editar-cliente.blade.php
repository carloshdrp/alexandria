<div class="page">
    <x-ui.breadcrumb
        :trilha="['Clientes' => route('clientes.index'), $cliente->name => route('emprestimos.clientes.situacao', $cliente), 'Editar' => null]"/>

    <div class="page-header">
        <div>
            <h1 class="page-title">Editar cliente</h1>
            <p class="page-subtitle">Dados de cadastro de {{ $cliente->name }}.</p>
        </div>
        <div class="page-actions">
            @if ($cliente->hasVerifiedEmail())
                <x-ui.badge tom="success">E-mail confirmado</x-ui.badge>
            @else
                <x-ui.badge tom="warning">E-mail não confirmado</x-ui.badge>
            @endif
        </div>
    </div>

    <x-ui.toast/>

    <form wire:submit="salvar" class="painel form">
        <div class="form-row">
            <x-ui.campo label="Nome" wire:model="name" required/>
            <x-ui.campo label="E-mail" type="email" wire:model="email" required
                        hint="Trocar o e-mail exige nova confirmação pelo cliente."/>
        </div>
        <div class="form-row">
            <x-ui.campo label="CPF" wire:model="documento" mask="999.999.999-99" inputmode="numeric" required/>
            <x-ui.campo label="Telefone" wire:model="telefone" mask="(99) 99999-9999" inputmode="tel" hint="Opcional."/>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary" wire:loading.attr="disabled" wire:target="salvar">
                <x-lucide-save class="size-4"/>
                Salvar
            </button>
            <a href="{{ route('emprestimos.clientes.situacao', $cliente) }}" class="btn btn-outline">Cancelar</a>
        </div>
    </form>

    <div class="section mt-6">
        <h2 class="section-title">Senha</h2>
        <div class="painel">
            <p class="form-hint mb-3">A senha é um dado pessoal. Envie um link para o cliente redefinir a dele.</p>
            <x-ui.confirmacao
                titulo="Enviar link de recuperação"
                texto="Um link para redefinir a senha será enviado para {{ $cliente->email }}."
                gatilho="Enviar link de recuperação"
                rotulo="Enviar link"
                icone="mail"
                tom="primary"
                gatilho-classe="btn btn-outline"
                wire:click="enviarLinkDeSenha"
            />
        </div>
    </div>
</div>
