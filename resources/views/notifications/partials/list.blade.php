<div class="overflow-hidden rounded-xl border border-gray-200 bg-white">
    @forelse($notifications as $notification)
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
            <button type="submit" class="grid w-full gap-3 px-4 py-4 text-right hover:bg-gray-50 sm:grid-cols-[10px_minmax(0,1fr)_150px] sm:items-center {{ is_null($notification->read_at) ? 'bg-emerald-50/30' : '' }}">
                <span class="hidden h-2 w-2 rounded-full {{ $dotClass }} {{ $notification->read_at ? 'opacity-30' : '' }} sm:block"></span>

                <span class="min-w-0">
                    <span class="flex flex-wrap items-center gap-2">
                        <strong class="text-[11px] font-semibold text-gray-800">{{ $data['title'] ?? 'اعلان جدید' }}</strong>
                        @if(is_null($notification->read_at))
                            <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-[8px] font-semibold text-emerald-700">جدید</span>
                        @endif
                    </span>
                    <span class="mt-1.5 block text-[10px] leading-6 text-gray-500">{{ $data['message'] ?? '' }}</span>
                </span>

                <span class="text-right sm:text-left">
                    <span class="block text-[8px] text-gray-400">{{ app(\App\Support\DatePresenter::class)->dateTime($notification->created_at) }}</span>
                    <span class="mt-1.5 inline-block text-[9px] font-semibold text-emerald-700">مشاهده ←</span>
                </span>
            </button>
        </form>
    @empty
        <div class="px-4 py-12 text-center">
            <strong class="block text-xs text-gray-700">اعلانی برای نمایش وجود ندارد</strong>
            <p class="mt-1 text-[10px] text-gray-400">اعلان‌های شخصی شما بعد از رویدادهای مرتبط در این بخش ثبت می‌شوند.</p>
        </div>
    @endforelse
</div>

@if($notifications->hasPages())
    <div class="mt-4">{{ $notifications->links() }}</div>
@endif
