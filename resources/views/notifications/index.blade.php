<x-layouts.app title="اعلان‌های من">
    <section class="space-y-4">
        <header class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-lg font-bold text-gray-900">اعلان‌های من</h1>
                <p class="mt-1 text-[10px] leading-5 text-gray-500">فقط اعلان‌های مرتبط با حساب کاربری شما نمایش داده می‌شوند.</p>
            </div>

            @if($unreadCount > 0)
                <form method="POST" action="{{ route('notifications.read-all') }}">
                    @csrf
                    <button class="btn btn-secondary text-[10px]" type="submit">خواندن همه ({{ number_format($unreadCount) }})</button>
                </form>
            @endif
        </header>

        <div class="flex items-center gap-2">
            <a href="{{ route('notifications.index') }}" class="rounded-lg border px-3 py-2 text-[10px] {{ $filter !== 'unread' ? 'border-emerald-200 bg-emerald-50 font-semibold text-emerald-700' : 'border-gray-200 bg-white text-gray-500' }}">همه</a>
            <a href="{{ route('notifications.index', ['filter' => 'unread']) }}" class="rounded-lg border px-3 py-2 text-[10px] {{ $filter === 'unread' ? 'border-emerald-200 bg-emerald-50 font-semibold text-emerald-700' : 'border-gray-200 bg-white text-gray-500' }}">خوانده‌نشده</a>
        </div>

        @include('notifications.partials.list', [
            'notifications' => $notifications,
            'openRoute' => 'notifications.open',
        ])
    </section>
</x-layouts.app>
