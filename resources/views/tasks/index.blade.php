<x-layouts.app title="تسک‌ها">
    <x-page-header :title="$scope === 'all' ? 'همه تسک‌ها' : 'تسک‌های من'" description="کارهای جاری، اولویت‌ها و ددلاین‌ها">
        <x-slot:actions>@can('tasks.create')<a href="{{ route('tasks.create') }}" class="btn btn-primary">ایجاد تسک</a>@endcan</x-slot:actions>
    </x-page-header>

    @can('tasks.view_all')
        <div class="mb-4 flex gap-2 border-b border-gray-200">
            <a href="{{ route('tasks.index', array_merge(request()->except(['scope', 'page']), ['scope' => 'mine'])) }}" class="border-b-2 px-3 py-2 font-semibold {{ $scope === 'mine' ? 'border-emerald-500 text-emerald-700' : 'border-transparent text-gray-500' }}">تسک‌های من</a>
            <a href="{{ route('tasks.index', array_merge(request()->except(['scope', 'page']), ['scope' => 'all'])) }}" class="border-b-2 px-3 py-2 font-semibold {{ $scope === 'all' ? 'border-emerald-500 text-emerald-700' : 'border-transparent text-gray-500' }}">همه تسک‌ها</a>
        </div>
    @endcan

    <div class="mb-4 flex gap-2 overflow-x-auto pb-1">
        @foreach(['all' => 'همه', 'today' => 'امروز', 'overdue' => 'عقب‌افتاده', 'in_progress' => 'در حال انجام', 'completed' => 'تکمیل‌شده'] as $value => $label)
            <a href="{{ route('tasks.index', array_merge(request()->except(['quick', 'page']), $value === 'all' ? [] : ['quick' => $value])) }}" class="btn shrink-0 {{ $quick === $value ? 'btn-primary' : 'btn-secondary' }}">{{ $label }}</a>
        @endforeach
    </div>

    <form method="GET" class="panel mb-4 p-3" x-data="{ filters: {{ request()->hasAny(['assignee_id', 'customer_id', 'priority', 'status']) ? 'true' : 'false' }} }">
        @if($scope === 'all')<input type="hidden" name="scope" value="all">@endif
        @if($quick !== 'all')<input type="hidden" name="quick" value="{{ $quick }}">@endif
        <div class="flex flex-col gap-2 sm:flex-row">
            <label for="task-search" class="sr-only">جستجوی تسک</label>
            <input id="task-search" class="form-control" name="q" value="{{ $search }}" placeholder="جستجو در عنوان، توضیحات، مشتری یا مسئول">
            <x-button variant="secondary" class="sm:w-28">جستجو</x-button>
            <button type="button" class="btn btn-secondary sm:w-28" @click="filters = !filters">فیلترها</button>
        </div>
        <div x-cloak x-show="filters" class="mt-3 grid gap-3 border-t border-gray-100 pt-3 sm:grid-cols-2 lg:grid-cols-4">
            @can('tasks.view_all')
                <select name="assignee_id" class="form-control"><option value="">همه مسئول‌ها</option>@foreach($assignees as $assignee)<option value="{{ $assignee->id }}" @selected($assigneeId === $assignee->id)>{{ $assignee->name }}</option>@endforeach</select>
            @endcan
            <select name="customer_id" class="form-control"><option value="">همه مشتری‌ها</option>@foreach($customers as $customer)<option value="{{ $customer->id }}" @selected($customerId === $customer->id)>{{ $customer->name }}</option>@endforeach</select>
            <select name="priority" class="form-control"><option value="">همه اولویت‌ها</option>@foreach($priorities as $item)<option value="{{ $item->value }}" @selected($priority === $item->value)>{{ $item->label() }}</option>@endforeach</select>
            <select name="status" class="form-control"><option value="">همه وضعیت‌ها</option>@foreach($statuses as $item)<option value="{{ $item->value }}" @selected($status === $item->value)>{{ $item->label() }}</option>@endforeach</select>
        </div>
    </form>

    <div class="panel overflow-hidden">
        @if($tasks->isEmpty())
            <x-empty-state message="فعلاً تسکی برای شما وجود ندارد.">
                <x-slot:action>@can('tasks.create')<a class="btn btn-primary" href="{{ route('tasks.create') }}">ایجاد تسک جدید</a>@endcan</x-slot:action>
            </x-empty-state>
        @else
            <div class="hidden overflow-x-auto md:block">
                <table class="w-full min-w-[950px] text-right">
                    <thead class="bg-gray-50 text-xs text-gray-500"><tr><th class="p-4">عنوان</th><th class="p-4">مشتری</th><th class="p-4">مسئول</th><th class="p-4">اولویت</th><th class="p-4">وضعیت</th><th class="p-4">شروع</th><th class="p-4">ددلاین</th></tr></thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($tasks as $task)
                            <tr>
                                <td class="p-4"><a class="font-semibold hover:text-emerald-700" href="{{ route('tasks.show', $task) }}">{{ $task->title }}</a></td>
                                <td class="p-4 text-gray-600">{{ $task->customer?->name ?: '—' }}</td>
                                <td class="p-4">{{ $task->assignee->name }}</td>
                                <td class="p-4"><x-badge :type="$task->priority->intent()">{{ $task->priority->label() }}</x-badge></td>
                                <td class="p-4"><x-badge :type="$task->status->intent()">{{ $task->status->label() }}</x-badge></td>
                                <td class="p-4">{{ app(\App\Support\DatePresenter::class)->date($task->start_date) }}</td>
                                <td class="p-4"><span class="{{ $task->isOverdue() ? 'font-semibold text-red-700' : '' }}">{{ app(\App\Support\DatePresenter::class)->date($task->due_date) }}</span>@if($task->isOverdue()) <x-badge type="danger" class="mr-1">عقب‌افتاده</x-badge>@endif</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="divide-y divide-gray-100 md:hidden">
                @foreach($tasks as $task)
                    <a href="{{ route('tasks.show', $task) }}" class="block min-h-32 p-4 active:bg-gray-50">
                        <div class="flex items-start justify-between gap-3"><strong class="leading-6">{{ $task->title }}</strong>@if($task->isOverdue())<x-badge type="danger">عقب‌افتاده</x-badge>@endif</div>
                        @if($task->customer)<p class="mt-1 text-sm text-gray-500">{{ $task->customer->name }}</p>@endif
                        <div class="mt-3 flex flex-wrap gap-2"><x-badge :type="$task->priority->intent()">{{ $task->priority->label() }}</x-badge><x-badge :type="$task->status->intent()">{{ $task->status->label() }}</x-badge></div>
                        <div class="mt-3 flex items-center justify-between text-sm"><span>ددلاین: {{ app(\App\Support\DatePresenter::class)->date($task->due_date) }}</span>@can('tasks.view_all')<span class="text-gray-500">{{ $task->assignee->name }}</span>@endcan</div>
                    </a>
                @endforeach
            </div>
            <div class="border-t border-gray-100 p-4">{{ $tasks->links() }}</div>
        @endif
    </div>
</x-layouts.app>
