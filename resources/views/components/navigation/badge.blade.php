@props(['tone' => 'warning'])
<span {{ $attributes->class([
    'sidebar-badge',
    'sidebar-badge-warning' => $tone === 'warning',
    'sidebar-badge-danger' => $tone === 'danger',
    'sidebar-badge-violet' => $tone === 'violet',
]) }}>{{ $slot }}</span>
