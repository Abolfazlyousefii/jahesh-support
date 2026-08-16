@props(['type' => 'neutral'])
<span {{ $attributes->class(['inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold', 'bg-emerald-50 text-emerald-700' => $type === 'success', 'bg-red-50 text-red-700' => $type === 'danger', 'bg-gray-100 text-gray-700' => $type === 'neutral']) }}>{{ $slot }}</span>
