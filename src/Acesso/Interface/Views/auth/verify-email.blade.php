<x-layouts::app title="Confirme seu e-mail">
    <div class="page-narrow">
        <div class="auth-card">
            <h1 class="auth-title">Confirme seu e-mail</h1>
            <p class="auth-subtitle">
                Enviamos um link de confirmação para <strong>{{ auth()->user()->email }}</strong>.
                Clique nele para liberar o acesso ao catálogo, aos empréstimos e às reservas.
            </p>

            @if (session('status') === 'verification-link-sent')
                <x-ui.alerta tipo="success">Um novo link de confirmação foi enviado.</x-ui.alerta>
            @endif

            <div class="form-actions">
                <form method="POST" action="{{ route('verification.send') }}">
                    @csrf
                    <button type="submit" class="btn btn-primary">
                        <x-lucide-mail class="size-4"/>
                        Reenviar link
                    </button>
                </form>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="btn btn-outline">Sair</button>
                </form>
            </div>

            <p class="auth-footer">Não recebeu? Verifique a caixa de spam antes de reenviar.</p>
        </div>
    </div>
</x-layouts::app>
