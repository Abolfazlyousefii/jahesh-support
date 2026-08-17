<x-layouts.portal title="درخواست‌های من">
    <x-page-header title="درخواست‌های من"><x-slot:actions><a href="{{ route('portal.tickets.create') }}" class="btn btn-primary">درخواست جدید</a></x-slot:actions></x-page-header>
    <div class="panel overflow-hidden">
        @forelse($tickets as $ticket)
            <a href="{{ route('portal.tickets.show', $ticket) }}" class="block min-h-24 border-b border-gray-100 p-4 last:border-0 active:bg-gray-50"><div class="flex items-start justify-between gap-3"><div><strong class="leading-6">{{ $ticket->subject }}</strong><span class="mt-1 block text-xs text-gray-500">#{{ $ticket->id }}</span></div><x-badge :type="$ticket->status->intent()">{{ $ticket->status->label() }}</x-badge></div><div class="mt-3 flex items-center justify-between"><x-badge :type="$ticket->priority->intent()">{{ $ticket->priority->label() }}</x-badge><span class="text-xs text-gray-500">{{ app(\App\Support\DatePresenter::class)->dateTime($ticket->updated_at) }}</span></div></a>
        @empty
            <x-empty-state message="هنوز درخواستی ثبت نکرده‌اید."><x-slot:action><a class="btn btn-primary" href="{{ route('portal.tickets.create') }}">ثبت اولین درخواست</a></x-slot:action></x-empty-state>
        @endforelse
        @if($tickets->hasPages())<div class="border-t border-gray-100 p-4">{{ $tickets->links() }}</div>@endif
    </div>
</x-layouts.portal>
