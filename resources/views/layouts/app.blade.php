<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? config('app.name') }}</title>
    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="app-shell">
<nav class="app-nav">
    <div class="app-nav-inner">
        <a href="{{ route('home') }}" class="app-brand">
            <x-lucide-library class="size-5"/>
            {{ config('app.name') }}
        </a>

        @auth
            <div class="nav-links">
                <a href="{{ route('emprestimos.catalogo') }}" @class(['nav-link', 'nav-link-active' => request()->routeIs('emprestimos.catalogo*')])>
                    <x-lucide-book-open class="size-4"/>
                    Catálogo
                </a>

                @can('bibliotecario')
                    <a href="{{ route('emprestimos.em-aberto') }}" @class(['nav-link', 'nav-link-active' => request()->routeIs('emprestimos.em-aberto')])>
                        <x-lucide-book-marked class="size-4"/>
                        Empréstimos
                    </a>
                    <a href="{{ route('emprestimos.reservas') }}" @class(['nav-link', 'nav-link-active' => request()->routeIs('emprestimos.reservas')])>
                        <x-lucide-bookmark class="size-4"/>
                        Reservas
                    </a>
                    <a href="{{ route('emprestimos.multas') }}" @class(['nav-link', 'nav-link-active' => request()->routeIs('emprestimos.multas')])>
                        <x-lucide-circle-dollar-sign class="size-4"/>
                        Multas
                    </a>
                    <a href="{{ route('clientes.index') }}" @class(['nav-link', 'nav-link-active' => request()->routeIs('clientes.*') || request()->routeIs('emprestimos.clientes.*')])>
                        <x-lucide-users class="size-4"/>
                        Clientes
                    </a>
                    <a href="{{ route('inventario.obras') }}" @class(['nav-link', 'nav-link-active' => request()->routeIs('inventario.*')])>
                        <x-lucide-package class="size-4"/>
                        Inventário
                    </a>
                @else
                    <a href="{{ route('emprestimos.meus') }}" @class(['nav-link', 'nav-link-active' => request()->routeIs('emprestimos.meus')])>
                        <x-lucide-book-marked class="size-4"/>
                        Meus empréstimos
                    </a>
                    <a href="{{ route('emprestimos.minhas-reservas') }}" @class(['nav-link', 'nav-link-active' => request()->routeIs('emprestimos.minhas-reservas')])>
                        <x-lucide-bookmark class="size-4"/>
                        Minhas reservas
                    </a>
                    <a href="{{ route('emprestimos.minhas-multas') }}" @class(['nav-link', 'nav-link-active' => request()->routeIs('emprestimos.minhas-multas')])>
                        <x-lucide-circle-dollar-sign class="size-4"/>
                        Minhas multas
                    </a>
                @endcan
            </div>

            <div class="nav-user">
                <a href="{{ route('perfil') }}" @class(['nav-link', 'nav-link-active' => request()->routeIs('perfil')])>
                    <x-lucide-circle-user class="size-4"/>
                    {{ auth()->user()->name }}
                </a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-outline">
                        <x-lucide-log-out class="size-4"/>
                        Sair
                    </button>
                </form>
            </div>
        @endauth
    </div>
</nav>

<main>
    {{ $slot }}
</main>
</body>
</html>
