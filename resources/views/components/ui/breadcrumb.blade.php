@props(['trilha' => []])

<div class="breadcrumbs mb-4 py-0 text-sm text-stone-500">
    <ul>
        <li>
            <a href="{{ route('home') }}" class="flex items-center gap-1.5">
                <x-lucide-house class="size-4"/>
                Início
            </a>
        </li>
        @foreach ($trilha as $rotulo => $url)
            <li>
                @if ($url && ! $loop->last)
                    <a href="{{ $url }}">{{ $rotulo }}</a>
                @else
                    <span class="text-stone-900">{{ $rotulo }}</span>
                @endif
            </li>
        @endforeach
    </ul>
</div>
