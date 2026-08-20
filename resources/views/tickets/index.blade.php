<x-layouts.app title="تیکت‌ها">
    <x-page-header title="تیکت‌ها" description="مدیریت درخواست‌ها، گفتگو با مشتری و ارجاع به تیم" />

    @can('tickets.view_all')
        <div class="mb-4 inline-flex rounded-lg border border-gray-200 bg-white p-1">
            <a href="{{ route('tickets.index', array_merge(request()->except(['scope', 'page']), ['scope' => 'all'])) }}" class="btn {{ $scope === 'all' ? 'btn-primary' : 'btn-secondary' }}">همه تیکت‌ها</a>
            <a href="{{ route('tickets.index', array_merge(request()->except(['scope', 'page']), ['scope' => 'mine'])) }}" class="btn {{ $scope === 'mine' ? 'btn-primary' : 'btn-secondary' }}">تیکت‌های من</a>
        </div>
    @endcan

    <div class="mb-4 flex gap-2 overflow-x-auto pb-1" aria-label="فیلتر وضعیت تیکت‌ها">
        <a href="{{ route('tickets.index', request()->except(['quick', 'page'])) }}" class="flex min-h-10 shrink-0 items-center gap-2 rounded-lg border px-3 text-[11px] font-semibold {{ $quick === null ? 'border-emerald-300 bg-emerald-50 text-emerald-800' : 'border-gray-200 bg-white text-gray-600 hover:border-gray-300' }}">
            <span>همه</span>
            <b class="numeric rounded-md bg-white px-1.5 py-0.5 text-[10px]">{{ $statusCounts->sum() }}</b>
        </a>
        @foreach($statuses as $item)
            <a href="{{ route('tickets.index', array_merge(request()->except(['quick', 'page']), ['quick' => $item->value])) }}" class="flex min-h-10 shrink-0 items-center gap-2 rounded-lg border px-3 text-[11px] font-semibold {{ $quick === $item->value ? 'border-emerald-300 bg-emerald-50 text-emerald-800' : 'border-gray-200 bg-white text-gray-600 hover:border-gray-300' }}">
                <span>{{ $item->label() }}</span>
                <b class="numeric rounded-md bg-white px-1.5 py-0.5 text-[10px]">{{ $statusCounts[$item->value] ?? 0 }}</b>
            </a>
        @endforeach
    </div>

    <form method="GET" class="panel mb-4 p-3" x-data="{ filters: {{ request()->hasAny(['assignee_id', 'customer_id', 'priority', 'status']) ? 'true' : 'false' }} }">
        @if($quick)<input type="hidden" name="quick" value="{{ $quick }}">@endif
        @if($scope)<input type="hidden" name="scope" value="{{ $scope }}">@endif
        @if($unreadOnly)<input type="hidden" name="unread" value="1">@endif

        <div class="flex flex-col gap-2 lg:flex-row">
            <label for="ticket-search" class="sr-only">جستجو</label>
            <input id="ticket-search" name="q" value="{{ $search }}" class="form-control flex-1" placeholder="جستجو در موضوع، مشتری یا شماره موبایل">
            <x-button variant="secondary" class="lg:w-28">جستجو</x-button>
            <button type="button" class="btn btn-secondary lg:w-28" @click="filters = !filters">فیلترها</button>
            <a href="{{ route('tickets.index', array_merge(request()->except(['unread', 'page']), ['unread' => 1])) }}" class="btn {{ $unreadOnly ? 'btn-primary' : 'btn-secondary' }} lg:min-w-36">
                پاسخ جدید مشتری
                @if($unreadCount > 0)<span class="mr-1 rounded-full bg-white/80 px-1.5 text-xs text-gray-800">{{ $unreadCount }}</span>@endif
            </a>
        </div>

        <div x-cloak x-show="filters" class="mt-3 grid gap-3 border-t border-gray-100 pt-3 sm:grid-cols-2 lg:grid-cols-4">
            @can('tickets.view_all')
                <select name="assignee_id" class="form-control">
                    <option value="">همه مسئول‌ها</option>
                    <option value="unassigned" @selected($unassignedOnly)>تخصیص‌نیافته</option>
                    @foreach($assignees as $assignee)<option value="{{ $assignee->id }}" @selected($assigneeId === $assignee->id)>{{ $assignee->name }}</option>@endforeach
                </select>
            @endcan
            <select name="customer_id" class="form-control">
                <option value="">همه مشتری‌ها</option>
                @foreach($customers as $customer)<option value="{{ $customer->id }}" @selected($customerId === $customer->id)>{{ $customer->name }}</option>@endforeach
            </select>
            <select name="priority" class="form-control">
                <option value="">همه اولویت‌ها</option>
                @foreach($priorities as $item)<option value="{{ $item->value }}" @selected($priority === $item->value)>{{ $item->label() }}</option>@endforeach
            </select>
            <select name="status" class="form-control">
                <option value="">همه وضعیت‌ها</option>
                @foreach($statuses as $item)<option value="{{ $item->value }}" @selected($status === $item->value)>{{ $item->label() }}</option>@endforeach
            </select>
        </div>
    </form>

    <div class="panel overflow-hidden">
        @if($tickets->isEmpty())
            <x-empty-state message="تیکتی برای نمایش وجود ندارد." />
        @else
            <div class="hidden md:block">
                <div class="divide-y divide-gray-100">
                    @foreach($tickets as $ticket)
                        @php($unread = $ticket->hasUnreadCustomerReply())
                        <a href="{{ route('tickets.show', $ticket) }}" class="grid grid-cols-[minmax(0,1.65fr)_minmax(130px,.65fr)_minmax(110px,.5fr)_120px_145px] items-center gap-4 px-5 py-4 transition hover:bg-gray-50 {{ $unread ? 'bg-emerald-50/40' : '' }}">
                            <div class="min-w-0">
                                <div class="flex items-center gap-2">
                                    @if($unread)<span class="h-2.5 w-2.5 shrink-0 rounded-full bg-emerald-500" title="پیام جدید مشتری"></span>@endif
                                    <strong class="truncate">{{ $ticket->subject }}</strong>
                                    <span class="text-xs text-gray-400">#{{ $ticket->id }}</span>
                                </div>
                                <div class="mt-1 truncate text-xs text-gray-500">
                                    {{ $ticket->latestPublicMessage?->body ?: 'بدون پیام' }}
                                </div>
                            </div>
                            <div class="min-w-0">
                                <strong class="block truncate text-sm">{{ $ticket->customer->name }}</strong>
                                <span class="mt-1 block truncate text-xs text-gray-500">{{ $ticket->customer->company_name ?: 'بدون مجموعه' }}</span>
                            </div>
                            <div class="min-w-0">
                                <span class="block text-[10px] text-gray-400">مسئول</span>
                                <strong class="mt-1 block truncate text-[11px] font-semibold text-gray-700">{{ $ticket->assignee?->name ?: 'تخصیص‌نیافته' }}</strong>
                            </div>
                            <div class="flex flex-col items-start gap-1.5">
                                <x-badge :type="$ticket->status->intent()">{{ $ticket->status->label() }}</x-badge>
                                <x-badge :type="$ticket->priority->intent()">{{ $ticket->priority->label() }}</x-badge>
                            </div>
                            <div class="text-left text-xs text-gray-500" dir="rtl">
                                <span class="block">آخرین فعالیت</span>
                                <strong class="mt-1 block font-medium text-gray-700">{{ app(\App\Support\DatePresenter::class)->dateTime($ticket->updated_at) }}</strong>
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>

            <div class="divide-y divide-gray-100 md:hidden">
                @foreach($tickets as $ticket)
                    @php($unread = $ticket->hasUnreadCustomerReply())
                    <a href="{{ route('tickets.show', $ticket) }}" class="block p-4 active:bg-gray-50 {{ $unread ? 'bg-emerald-50/40' : '' }}">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <div class="flex items-center gap-2">
                                    @if($unread)<span class="h-2.5 w-2.5 shrink-0 rounded-full bg-emerald-500"></span>@endif
                                    <strong class="truncate leading-6">{{ $ticket->subject }}</strong>
                                </div>
                                <span class="mt-1 block text-xs text-gray-500">#{{ $ticket->id }} — {{ $ticket->customer->name }}</span>
                            </div>
                            <x-badge :type="$ticket->status->intent()">{{ $ticket->status->label() }}</x-badge>
                        </div>
                        <p class="mt-3 line-clamp-2 text-sm leading-6 text-gray-600">{{ $ticket->latestPublicMessage?->body }}</p>
                        <div class="mt-3 flex items-center justify-between gap-3">
                            <div class="flex items-center gap-2"><x-badge :type="$ticket->priority->intent()">{{ $ticket->priority->label() }}</x-badge><span class="text-xs text-gray-500">{{ $ticket->assignee?->name ?: 'تخصیص‌نیافته' }}</span></div>
                            <span class="text-xs text-gray-400">{{ app(\App\Support\DatePresenter::class)->dateTime($ticket->updated_at) }}</span>
                        </div>
                    </a>
                @endforeach
            </div>

            <div class="border-t border-gray-100 p-4">{{ $tickets->links() }}</div>
        @endif
    </div>
</x-layouts.app>
