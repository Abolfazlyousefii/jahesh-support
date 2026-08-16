@props(['message'])
<div class="px-4 py-12 text-center text-gray-500"><p>{{ $message }}</p>@isset($action)<div class="mt-4">{{ $action }}</div>@endisset</div>
