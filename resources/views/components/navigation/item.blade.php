@props([
    'href',
    'label',
    'active' => false,
    'icon' => null,
    'topLevel' => false,
])
<a
    href="{{ $href }}"
    @if($active) aria-current="page" @endif
    @click="if (typeof drawer !== 'undefined') drawer = false"
    {{ $attributes->class([
        'sidebar-item sidebar-direct-item' => $topLevel,
        'sidebar-submenu-link' => ! $topLevel,
        'active' => $active,
    ]) }}
>
    @if($topLevel)
        <span class="sidebar-item-main">
            @if($icon)<x-icon :name="$icon" />@endif
            <span class="sidebar-item-label">{{ $label }}</span>
        </span>
    @else
        <span>{{ $label }}</span>
    @endif
    @isset($badge)<span class="ms-auto">{{ $badge }}</span>@endisset
</a>
