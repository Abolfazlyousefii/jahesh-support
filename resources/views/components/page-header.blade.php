@props(['title', 'description' => null])
<div {{ $attributes->class('mb-5 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between') }}>
    <div class="min-w-0"><h1 class="text-xl font-extrabold tracking-tight text-slate-900 sm:text-[22px]">{{ $title }}</h1>@if($description)<p class="mt-1.5 max-w-3xl text-[11px] leading-6 text-gray-500">{{ $description }}</p>@endif</div>
    @isset($actions)<div class="flex shrink-0 flex-wrap gap-2">{{ $actions }}</div>@endisset
</div>
