<x-mail::message>
{{-- Título --}}
@if (! empty($greeting))
# {{ $greeting }}
@elseif (! empty($subject))
# {{ $subject }}
@endif

{{-- Corpo --}}
@foreach ($introLines as $line)
{{ $line }}

@endforeach

{{-- Ação --}}
@isset($actionText)
<?php
$color = match ($level) {
    'success', 'error' => $level,
    default => 'primary',
};
?>
<x-mail::button :url="$actionUrl" :color="$color">
{{ $actionText }}
</x-mail::button>
@endisset

{{-- Complemento --}}
@foreach ($outroLines as $line)
{{ $line }}

@endforeach

{{-- Assinatura --}}
@if (! empty($salutation))
{{ $salutation }}
@endif

{{-- Subcopy --}}
@isset($actionText)
<x-slot:subcopy>
Se o botão “{{ $actionText }}” não funcionar, copie e cole o endereço abaixo no seu navegador:
<span class="break-all">[{{ $displayableActionUrl }}]({{ $actionUrl }})</span>
</x-slot:subcopy>
@endisset
</x-mail::message>
