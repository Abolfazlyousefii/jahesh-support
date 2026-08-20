@props(['label', 'value', 'description' => null, 'tone' => 'neutral', 'href' => null])
@php($tag = $href ? 'a' : 'section')
<{{ $tag }} @if($href) href="{{ $href }}" @endif {{ $attributes->class('panel flex min-h-24 items-start justify-between gap-4 p-4') }}>
    <span>
        <small class="block text-[10px] font-medium text-gray-500">{{ $label }}</small>
        <strong class="numeric mt-2 block text-xl font-extrabold text-slate-900">{{ $value }}</strong>
        @if($description)<span class="mt-1 block text-[9px] text-gray-400">{{ $description }}</span>@endif
    </span>
    <i @class([
        'mt-1 h-2 w-2 rounded-full not-italic',
        'bg-amber-500' => $tone === 'warning',
        'bg-red-500' => $tone === 'danger',
        'bg-blue-500' => $tone === 'info',
        'bg-violet-500' => $tone === 'violet',
        'bg-emerald-500' => $tone === 'success',
        'bg-gray-400' => $tone === 'neutral',
    ])></i>
</{{ $tag }}>
