<x-layouts.app title="تیکت‌ها">
    <x-page-header title="تیکت‌ها" description="درخواست‌های پشتیبانی مشتریان" />

    <div class="mb-4 flex gap-2 overflow-x-auto pb-1">
        <a href="{{ route('tickets.index', request()->except(['quick', 'page'])) }}" class="btn shrink-0 {{ $quick === null ? 'btn-primary' : 'btn-secondary' }}">همه</a>
        @foreach($statuses as $item)<a href="{{ route('tickets.index', array_merge(request()->except(['quick', 'page']), ['quick' => $item->value])) }}" class="btn shrink-0 {{ $quick === $item->value ? 'btn-primary' : 'btn-secondary' }}">{{ $item->label() }}</a>@endforeach
    </div>

    <form method="GET" class="panel mb-4 p-3" x-data="{ filters: {{ request()->hasAny(['assignee_id', 'customer_id', 'priority', 'status']) ? 'true' : 'false' }} }">
        @if($quick)<input type="hidden" name="quick" value="{{ $quick }}">@endif
        <div class="flex flex-col gap-2 sm:flex-row"><label for="ticket-search" class="sr-only">جستجو</label><input id="ticket-search" name="q" value="{{ $search }}" class="form-control" placeholder="جستجو در موضوع، مشتری یا شماره موبایل"><x-button variant="secondary" class="sm:w-28">جستجو</x-button><button type="button" class="btn btn-secondary sm:w-28" @click="filters = !filters">فیلترها</button></div>
        <div x-cloak x-show="filters" class="mt-3 grid gap-3 border-t border-gray-100 pt-3 sm:grid-cols-2 lg:grid-cols-4">
            @can('tickets.view_all')<select name="assignee_id" class="form-control"><option value="">همه مسئول‌ها</option>@foreach($assignees as $assignee)<option value="{{ $assignee->id }}" @selected($assigneeId === $assignee->id)>{{ $assignee->name }}</option>@endforeach</select>@endcan
            <select name="customer_id" class="form-control"><option value="">همه مشتری‌ها</option>@foreach($customers as $customer)<option value="{{ $customer->id }}" @selected($customerId === $customer->id)>{{ $customer->name }}</option>@endforeach</select>
            <select name="priority" class="form-control"><option value="">همه اولویت‌ها</option>@foreach($priorities as $item)<option value="{{ $item->value }}" @selected($priority === $item->value)>{{ $item->label() }}</option>@endforeach</select>
            <select name="status" class="form-control"><option value="">همه وضعیت‌ها</option>@foreach($statuses as $item)<option value="{{ $item->value }}" @selected($status === $item->value)>{{ $item->label() }}</option>@endforeach</select>
        </div>
    </form>

    <div class="panel overflow-hidden">
        @if($tickets->isEmpty())
            <x-empty-state message="تیکتی برای نمایش وجود ندارد." />
        @else
            <div class="hidden overflow-x-auto md:block"><table class="w-full min-w-[900px] text-right"><thead class="bg-gray-50 text-xs text-gray-500"><tr><th class="p-4">موضوع</th><th class="p-4">مشتری</th><th class="p-4">اولویت</th><th class="p-4">وضعیت</th><th class="p-4">مسئول</th><th class="p-4">آخرین بروزرسانی</th></tr></thead><tbody class="divide-y divide-gray-100">@foreach($tickets as $ticket)<tr><td class="p-4"><a class="font-semibold hover:text-emerald-700" href="{{ route('tickets.show', $ticket) }}">{{ $ticket->subject }}</a><span class="mr-2 text-xs text-gray-400">#{{ $ticket->id }}</span></td><td class="p-4">{{ $ticket->customer->name }}</td><td class="p-4"><x-badge :type="$ticket->priority->intent()">{{ $ticket->priority->label() }}</x-badge></td><td class="p-4"><x-badge :type="$ticket->status->intent()">{{ $ticket->status->label() }}</x-badge></td><td class="p-4">{{ $ticket->assignee?->name ?: 'تخصیص‌نیافته' }}</td><td class="p-4">{{ app(\App\Support\DatePresenter::class)->dateTime($ticket->updated_at) }}</td></tr>@endforeach</tbody></table></div>
            <div class="divide-y divide-gray-100 md:hidden">@foreach($tickets as $ticket)<a href="{{ route('tickets.show', $ticket) }}" class="block min-h-28 p-4 active:bg-gray-50"><div class="flex items-start justify-between gap-3"><div><strong class="leading-6">{{ $ticket->subject }}</strong><span class="mt-1 block text-xs text-gray-500">#{{ $ticket->id }} — {{ $ticket->customer->name }}</span></div><x-badge :type="$ticket->status->intent()">{{ $ticket->status->label() }}</x-badge></div><div class="mt-3 flex items-center justify-between"><x-badge :type="$ticket->priority->intent()">{{ $ticket->priority->label() }}</x-badge><span class="text-xs text-gray-500">{{ $ticket->assignee?->name ?: 'تخصیص‌نیافته' }}</span></div></a>@endforeach</div>
            <div class="border-t border-gray-100 p-4">{{ $tickets->links() }}</div>
        @endif
    </div>
</x-layouts.app>
