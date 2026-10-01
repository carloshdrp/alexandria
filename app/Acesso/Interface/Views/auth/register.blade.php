<x-layouts::app title="Cadastro">
    <div class="page-narrow">
        <div class="auth-card">
            <h1 class="auth-title">Criar conta</h1>
            <p class="auth-subtitle">Cadastre-se como leitor para reservar obras e acompanhar seus empréstimos.</p>

            <form method="POST" action="{{ route('register') }}" class="form">
                @csrf
                <x-ui.campo label="Nome" name="name" value="{{ old('name') }}" required autofocus autocomplete="name"/>
                <x-ui.campo label="E-mail" name="email" type="email" value="{{ old('email') }}" required
                            autocomplete="email"/>
                <x-ui.campo label="CPF" name="documento" value="{{ old('documento') }}" required inputmode="numeric"
                            mask="999.999.999-99" placeholder="000.000.000-00"/>
                <x-ui.campo label="Telefone" name="telefone" value="{{ old('telefone') }}" inputmode="tel"
                            mask="(99) 99999-9999" placeholder="(11) 99999-9999" hint="Opcional."/>
                <x-ui.campo label="Senha" name="password" type="password" required autocomplete="new-password"
                            hint="Mínimo de 8 caracteres."/>
                <x-ui.campo label="Confirmar senha" name="password_confirmation" type="password" required
                            autocomplete="new-password"/>
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Cadastrar</button>
                </div>
            </form>

            <p class="auth-footer">Já tem conta? <a href="{{ route('login') }}" class="link link-hover">Entrar</a></p>
        </div>
    </div>
</x-layouts::app>
