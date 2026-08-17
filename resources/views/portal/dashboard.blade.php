<x-layouts.portal title="خانه">
    <section class="panel p-5 sm:p-7"><p class="text-gray-500">سلام {{ $customer->name }}</p><h1 class="mt-2 text-xl font-bold">چه کاری می‌توانیم برای شما انجام دهیم؟</h1><a href="{{ route('portal.tickets.create') }}" class="btn btn-primary mt-5 w-full sm:w-auto">+ ثبت درخواست پشتیبانی</a></section>
    <section class="panel mt-4 overflow-hidden">
        <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4"><h2 class="font-bold">درخواست‌های من</h2><a href="{{ route('portal.tickets.index') }}" class="text-sm font-semibold text-emerald-700">مشاهده همه</a></div>
        @forelse($recentTickets as $ticket)
            <a href="{{ route('portal.tickets.show', $ticket) }}" class="flex min-h-16 items-center justify-between gap-3 border-b border-gray-100 px-5 py-3 last:border-0"><div><strong class="block">{{ $ticket->subject }}</strong><span class="mt-1 block text-xs text-gray-500">#{{ $ticket->id }}</span></div><x-badge :type="$ticket->status->intent()">{{ $ticket->status->label() }}</x-badge></a>
        @empty
            <div class="px-5 py-8 text-center text-sm text-gray-500">هنوز درخواستی ثبت نکرده‌اید.</div>
        @endforelse
    </section>
</x-layouts.portal>
