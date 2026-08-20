@props(['type' => 'neutral'])
<span {{ $attributes->class([
    'inline-flex items-center rounded-md border px-2 py-0.5 text-[10px] font-semibold leading-5',
    'border-emerald-100 bg-emerald-50 text-emerald-700' => $type === 'success',
    'border-red-100 bg-red-50 text-red-700' => $type === 'danger',
    'border-amber-100 bg-amber-50 text-amber-700' => $type === 'warning',
    'border-sky-100 bg-sky-50 text-sky-700' => $type === 'info',
    'border-violet-100 bg-violet-50 text-violet-700' => $type === 'violet',
    'border-gray-200 bg-gray-50 text-gray-600' => $type === 'neutral',
]) }}>{{ $slot }}</span>
