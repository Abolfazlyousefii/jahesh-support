@props(['title', 'description' => null])
<div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <div><h1 class="text-xl font-bold sm:text-[22px]">{{ $title }}</h1>@if($description)<p class="mt-1 text-sm text-gray-500">{{ $description }}</p>@endif</div>
    @isset($actions)<div class="flex gap-2">{{ $actions }}</div>@endisset
</div>
