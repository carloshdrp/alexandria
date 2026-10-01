@props(['tipo' => 'info', 'acao' => null])

@php
    $tipos = [
        'success' => 'alert-success',
        'error' => 'alert-error',
        'warning' => 'alert-warning',
        'info' => 'alert-info',
    ];
    $icones = [
        'success' => 'circle-check',
        'error' => 'circle-alert',
        'warning' => 'triangle-alert',
        'info' => 'info',
    ];
@endphp

<div {{ $attributes->merge(['class' => 'alert alert-soft '.($tipos[$tipo] ?? $tipos['info'])]) }} role="alert">
    <x-dynamic-component :component="'lucide-'.($icones[$tipo] ?? $icones['info'])" class="size-5 shrink-0"/>
    <span>{{ $slot }}</span>
    {{ $acao }}
</div>
