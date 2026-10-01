<div class="page">
    <x-ui.breadcrumb :trilha="['Clientes' => route('clientes.index'), 'Novo cliente' => null]"/>

    <div class="page-header">
        <div>
            <h1 class="page-title">Novo cliente</h1>
            <p class="page-subtitle">Mesmas regras do cadastro público: o cliente entra com o e-mail e a senha
                informados.</p>
        </div>
    </div>

    <x-ui.toast/>

    <form wire:submit="cadastrar" class="painel form">
        <div class="form-row">
            <x-ui.campo label="Nome" wire:model="name" required/>
            <x-ui.campo label="E-mail" type="email" wire:model="email" required/>
        </div>
        <div class="form-row">
            <x-ui.campo label="CPF" wire:model="documento" mask="999.999.999-99" placeholder="000.000.000-00"
                        inputmode="numeric" required/>
            <x-ui.campo label="Telefone" wire:model="telefone" mask="(99) 99999-9999" placeholder="(11) 99999-9999"
                        inputmode="tel" hint="Opcional."/>
        </div>
        <div class="form-row">
            <x-ui.campo label="Senha" type="password" wire:model="password" required autocomplete="new-password"/>
            <x-ui.campo label="Confirmar senha" type="password" wire:model="password_confirmation" required
                        autocomplete="new-password"/>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary" wire:loading.attr="disabled">
                <x-lucide-user-plus class="size-4"/>
                Cadastrar
            </button>
            <a href="{{ route('clientes.index') }}" class="btn btn-outline">Cancelar</a>
        </div>
    </form>
</div>
