@props([
    'guard' => 'web',
    'indexRoute' => 'notifications.index',
    'openRoute' => 'notifications.open',
    'readAllRoute' => 'notifications.read-all',
    'summaryRoute' => 'notifications.summary',
    'compact' => false,
])

@php
    $notificationOwner = auth($guard)->user();
    $headerNotifications = $notificationOwner?->notifications()->latest()->limit(6)->get() ?? collect();
    $headerUnreadCount = $notificationOwner?->unreadNotifications()->count() ?? 0;
@endphp

<div
    class="relative"
    x-data="{
        open: false,
        count: {{ (int) $headerUnreadCount }},
        async refreshCount() {
            try {
                const response = await fetch('{{ route($summaryRoute) }}', {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin'
                });
                if (response.ok) {
                    const data = await response.json();
                    this.count = Number(data.unread_count || 0);
                }
            } catch (_) {}
        }
    }"
    x-init="setInterval(() => refreshCount(), 30000); window.addEventListener('focus', () => refreshCount())"
    @keydown.escape.window="open = false"
>
    <button
        type="button"
        @click="open = !open"
        class="relative grid {{ $compact ? 'h-9 w-9' : 'h-11 w-11' }} place-items-center rounded-lg border border-gray-200 bg-white text-gray-600 transition hover:border-gray-300 hover:bg-gray-50"
        aria-label="اعلان‌های من"
        :aria-expanded="open.toString()"
    >
        <x-icon name="bell" class="h-5 w-5" />
        <span
            x-cloak
            x-show="count > 0"
            x-text="count > 99 ? '99+' : count"
            class="absolute -left-1 -top-1 min-w-5 rounded-full bg-rose-500 px-1 text-center text-[9px] font-bold leading-5 text-white ring-2 ring-white"
        ></span>
    </button>

    <div
        x-cloak
        x-show="open"
        x-transition.opacity.duration.120ms
        @click.outside="open = false"
                            class="absolute left-0 z-50 mt-2 w-[min(360px,calc(100vw-24px))] overflow-hidden rounded-xl border border-gray-200 bg-white shadow-lg"
    >
        <div class="flex items-center justify-between border-b border-gray-100 px-4 py-3">
            <div>
                <strong class="block text-xs text-gray-900">اعلان‌های من</strong>
                <span class="mt-0.5 block text-[9px] text-gray-400" x-text="count ? `${count} اعلان خوانده‌نشده` : 'همه اعلان‌ها خوانده شده‌اند'"></span>
            </div>

            @if($headerUnreadCount > 0)
                <form method="POST" action="{{ route($readAllRoute) }}">
                    @csrf
                    <button class="text-[9px] font-semibold text-emerald-700 hover:text-emerald-800" type="submit">خواندن همه</button>
                </form>
            @endif
        </div>

        <div class="max-h-[390px] overflow-y-auto">
            @forelse($headerNotifications as $notification)
                @php
                    $data = $notification->data;
                    $tone = $data['tone'] ?? 'neutral';
                    $dotClass = match ($tone) {
                        'red' => 'bg-rose-500',
                        'amber' => 'bg-amber-500',
                        'blue' => 'bg-blue-500',
                        'violet' => 'bg-violet-500',
                        'green' => 'bg-emerald-500',
                        default => 'bg-gray-400',
                    };
                @endphp

                <form method="POST" action="{{ route($openRoute, $notification->id) }}" class="border-b border-gray-100 last:border-0">
                    @csrf
                    <button type="submit" class="grid w-full grid-cols-[8px_minmax(0,1fr)] gap-3 px-4 py-3 text-right {{ is_null($notification->read_at) ? 'bg-emerald-50/40' : 'bg-white' }} hover:bg-gray-50">
                        <span class="mt-1.5 h-2 w-2 rounded-full {{ $dotClass }} {{ $notification->read_at ? 'opacity-35' : '' }}"></span>
                        <span class="min-w-0">
                            <strong class="block truncate text-[10px] font-semibold text-gray-800">{{ $data['title'] ?? 'اعلان جدید' }}</strong>
                            <span class="mt-1 block line-clamp-2 text-[9px] leading-5 text-gray-500">{{ $data['message'] ?? '' }}</span>
                            <span class="mt-1.5 block text-[8px] text-gray-400">{{ app(\App\Support\DatePresenter::class)->dateTime($notification->created_at) }}</span>
                        </span>
                    </button>
                </form>
            @empty
                <div class="px-4 py-8 text-center">
                    <strong class="block text-[11px] text-gray-700">اعلانی ندارید</strong>
                    <span class="mt-1 block text-[9px] text-gray-400">رویدادهای مرتبط با حساب شما اینجا نمایش داده می‌شوند.</span>
                </div>
            @endforelse
        </div>

        <a href="{{ route($indexRoute) }}" class="block border-t border-gray-100 px-4 py-3 text-center text-[10px] font-semibold text-emerald-700 hover:bg-gray-50">
            مشاهده همه اعلان‌ها
        </a>
    </div>
</div>
