<x-layouts::app title="Recuperar senha">
    <div class="page-narrow">
        <div class="auth-card">
            <h1 class="auth-title">Recuperar senha</h1>
            <p class="auth-subtitle">Informe seu e-mail e enviaremos um link para redefinir a senha.</p>

            @if (session('status'))
                <x-ui.alerta tipo="success">{{ session('status') }}</x-ui.alerta>
            @endif

            <form method="POST" action="{{ route('password.email') }}" class="form">
                @csrf
                <x-ui.campo label="E-mail" name="email" type="email" value="{{ old('email') }}" required autofocus/>
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Enviar link</button>
                    <a href="{{ route('login') }}" class="link link-hover">Voltar</a>
                </div>
            </form>
        </div>
    </div>
</x-layouts::app>
