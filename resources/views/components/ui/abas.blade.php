@props(['itens' => []])

<div class="tabs tabs-lift mb-6 w-full">
    @foreach ($itens as $rotulo => $rota)
        <a href="{{ route($rota) }}" @class(['tab', 'tab-active' => request()->routeIs($rota)])>{{ $rotulo }}</a>
    @endforeach
</div>
