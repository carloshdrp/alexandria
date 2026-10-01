@props([
    'label' => null,
    'name' => null,
    'type' => 'text',
    'hint' => null,
    'mask' => null,
    'erro' => null,
])

@php
    $modelo = $attributes->wire('model')->value();
    $campo = $erro ?? $name ?? $modelo;
    $id = $name ?? ($modelo ? str_replace(['.', '$'], '-', $modelo) : null);
    $invalido = $campo !== null && $errors->has($campo);

    $base = match ($type) {
        'select' => 'select',
        'textarea' => 'textarea',
        'checkbox' => 'checkbox',
        'file' => 'file-input',
        default => 'input',
    };

    $classes = $base.' w-full'.($invalido ? ' '.$base.'-error' : '');

    $atributos = $attributes->merge(['class' => $classes]);

    if ($mask) {
        $atributos = $atributos->merge(['x-data' => '', 'x-mask' => $mask]);
    }
@endphp

<div class="form-field">
    @if ($label)
        <label @if ($id) for="{{ $id }}" @endif class="form-label">{{ $label }}</label>
    @endif

    @if ($type === 'select')
        <select @if ($id) id="{{ $id }}"
                @endif @if ($name) name="{{ $name }}" @endif {{ $atributos }}>{{ $slot }}</select>
    @elseif ($type === 'textarea')
        <textarea @if ($id) id="{{ $id }}"
                  @endif @if ($name) name="{{ $name }}" @endif {{ $atributos }}>{{ $slot }}</textarea>
    @else
        <input @if ($id) id="{{ $id }}" @endif @if ($name) name="{{ $name }}"
               @endif type="{{ $type }}" {{ $atributos }}>
    @endif

    @if ($hint)
        <span class="form-hint">{{ $hint }}</span>
    @endif

    @if ($campo)
        @error($campo)
        <span class="form-error">{{ $message }}</span>
        @enderror
    @endif
</div>
