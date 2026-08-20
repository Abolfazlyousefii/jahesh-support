@props(['variant' => 'primary', 'type' => 'submit'])
<button type="{{ $type }}" {{ $attributes->class([
    'btn',
    'btn-primary' => $variant === 'primary',
    'btn-secondary' => $variant === 'secondary',
    'btn-danger' => $variant === 'danger',
    'btn-ghost' => $variant === 'ghost',
    'btn-text' => $variant === 'text',
]) }}>{{ $slot }}</button>
