@props(['url' => null, 'fallback' => null, 'titulo', 'prioritaria' => false])

@php
    $origem = $url ?? $fallback;
@endphp

@if ($origem === null)
    <div {{ $attributes->merge(['class' => 'obra-capa-vazia']) }}>{{ $titulo }}</div>
@else
    <img src="{{ $origem }}"
         alt="Capa de {{ $titulo }}"
         width="400"
         height="533"
         decoding="async"
         loading="{{ $prioritaria ? 'eager' : 'lazy' }}"
         fetchpriority="{{ $prioritaria ? 'high' : 'auto' }}"
         x-data="{ carregada: false }"
         x-init="carregada = $el.complete"
         x-on:load="carregada = true"
         :class="carregada || 'obra-capa-carregando'"
         {{ $attributes->merge(['class' => 'obra-capa']) }}>
@endif
