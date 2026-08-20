@props(['message' => null, 'title' => null, 'description' => null, 'icon' => 'tickets'])
<div {{ $attributes->class('px-5 py-12 text-center') }}>
    <span class="mx-auto grid h-10 w-10 place-items-center rounded-lg border border-gray-200 bg-gray-50 text-gray-400">
        <x-icon :name="$icon" class="h-5 w-5" />
    </span>
    <strong class="mt-3 block text-[13px] font-bold text-gray-800">{{ $title ?: $message }}</strong>
    @if($description)<p class="mx-auto mt-1.5 max-w-md text-[11px] leading-6 text-gray-500">{{ $description }}</p>@endif
    @isset($action)<div class="mt-4">{{ $action }}</div>@endisset
</div>
