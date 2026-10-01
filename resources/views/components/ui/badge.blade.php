@props(['tom' => 'neutral'])

@php
    $tons = [
        'success' => 'badge-success',
        'warning' => 'badge-warning',
        'danger' => 'badge-error',
        'info' => 'badge-info',
        'neutral' => 'badge-neutral',
    ];
@endphp

<span {{ $attributes->merge(['class' => 'badge badge-soft '.($tons[$tom] ?? $tons['neutral'])]) }}>{{ $slot }}</span>
