<x-layouts::app title="Redefinir senha">
    <div class="page-narrow">
        <div class="auth-card">
            <h1 class="auth-title">Redefinir senha</h1>
            <p class="auth-subtitle">Escolha uma nova senha para sua conta.</p>

            <form method="POST" action="{{ route('password.update') }}" class="form">
                @csrf
                <input type="hidden" name="token" value="{{ $request->route('token') }}">
                <x-ui.campo label="E-mail" name="email" type="email" value="{{ old('email', $request->email) }}"
                            required autofocus/>
                <x-ui.campo label="Nova senha" name="password" type="password" required autocomplete="new-password"/>
                <x-ui.campo label="Confirmar senha" name="password_confirmation" type="password" required
                            autocomplete="new-password"/>
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Redefinir</button>
                </div>
            </form>
        </div>
    </div>
</x-layouts::app>
