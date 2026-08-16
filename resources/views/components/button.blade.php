@props(['variant' => 'primary', 'type' => 'submit'])
<button type="{{ $type }}" {{ $attributes->class(['btn', 'btn-primary' => $variant === 'primary', 'btn-secondary' => $variant === 'secondary', 'btn-danger' => $variant === 'danger']) }}>{{ $slot }}</button>
