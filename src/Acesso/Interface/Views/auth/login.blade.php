<x-layouts::app title="Entrar">
    <div class="page-narrow">
        <div class="auth-card">
            <h1 class="auth-title">Entrar</h1>
            <p class="auth-subtitle">Acesse o catálogo, seus empréstimos e reservas.</p>

            @if (request()->boolean('bloqueado'))
                <x-ui.alerta tipo="error">Seu acesso foi bloqueado. Procure a biblioteca para regularizar.</x-ui.alerta>
            @endif

            @if (session('status'))
                <x-ui.alerta tipo="success">{{ session('status') }}</x-ui.alerta>
            @endif

            <form method="POST" action="{{ route('login') }}" class="form">
                @csrf
                <x-ui.campo label="E-mail" name="email" type="email" value="{{ old('email') }}" required autofocus
                            autocomplete="email"/>
                <x-ui.campo label="Senha" name="password" type="password" required autocomplete="current-password"/>
                <label class="form-check">
                    <input type="checkbox" name="remember" value="1" class="checkbox checkbox-sm">
                    Manter conectado
                </label>
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Entrar</button>
                    <a href="{{ route('password.request') }}" class="link link-hover">Esqueci a senha</a>
                </div>
            </form>

            <p class="auth-footer">Ainda não tem conta? <a href="{{ route('register') }}" class="link link-hover">Cadastre-se</a>
            </p>
        </div>
    </div>
</x-layouts::app>
