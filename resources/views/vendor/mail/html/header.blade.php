@props(['url'])
<tr>
    <td class="header">
        <a href="{{ $url }}" style="display: inline-block;">
            <span class="header-mark">{!! $slot !!}</span>
            <span class="header-sub">Biblioteca</span>
        </a>
    </td>
</tr>
