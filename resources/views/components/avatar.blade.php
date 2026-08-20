@props(['name', 'size' => 'md'])
<span {{ $attributes->class([
    'inline-grid shrink-0 place-items-center rounded-lg bg-slate-100 font-bold text-slate-600',
    'h-8 w-8 text-[10px]' => $size === 'sm',
    'h-10 w-10 text-xs' => $size === 'md',
    'h-12 w-12 text-sm' => $size === 'lg',
]) }} aria-hidden="true">{{ mb_substr($name, 0, 1) }}</span>
