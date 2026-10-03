<div class="page">
    <x-ui.breadcrumb :trilha="['Meu perfil' => null]"/>

    <div class="page-header">
        <div>
            <h1 class="page-title">Meu perfil</h1>
            <p class="page-subtitle">Seus dados de cadastro e a senha de acesso.</p>
        </div>
        <div class="page-actions">
            @if ($usuario->hasVerifiedEmail())
                <x-ui.badge tom="success">E-mail confirmado</x-ui.badge>
            @else
                <x-ui.badge tom="warning">E-mail não confirmado</x-ui.badge>
            @endif
        </div>
    </div>

    <x-ui.toast/>

    <div class="section">
        <h2 class="section-title">Dados</h2>
        <form wire:submit="salvar" class="painel form">
            <div class="form-row">
                <x-ui.campo label="Nome" wire:model="name" required/>
                <x-ui.campo label="E-mail" type="email" wire:model="email" required
                            hint="Trocar o e-mail exige uma nova confirmação."/>
            </div>
            <div class="form-row">
                <x-ui.campo label="CPF" wire:model="documento" mask="999.999.999-99" inputmode="numeric" required/>
                <x-ui.campo label="Telefone" wire:model="telefone" mask="(99) 99999-9999" inputmode="tel"
                            hint="Opcional."/>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary" wire:loading.attr="disabled" wire:target="salvar">
                    <x-lucide-save class="size-4"/>
                    Salvar
                </button>
            </div>
        </form>
    </div>

    <div class="section">
        <h2 class="section-title">Senha</h2>
        <form wire:submit="alterarSenha" class="painel form">
            <x-ui.campo label="Senha atual" type="password" wire:model="current_password" required
                        autocomplete="current-password"/>
            <div class="form-row">
                <x-ui.campo label="Nova senha" type="password" wire:model="password" required
                            autocomplete="new-password" hint="Mínimo de 8 caracteres."/>
                <x-ui.campo label="Confirmar nova senha" type="password" wire:model="password_confirmation" required
                            autocomplete="new-password"/>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary" wire:loading.attr="disabled" wire:target="alterarSenha">
                    <x-lucide-key-round class="size-4"/>
                    Alterar senha
                </button>
            </div>
        </form>
    </div>
</div>
