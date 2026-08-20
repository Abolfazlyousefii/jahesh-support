<x-layouts.app title="داشبورد مدیریتی">
    @php
        $companyName = $generalSettings['general.company_name'] ?? 'تیم جهش';
        $user = auth()->user();

        $actionTone = [
            'orange' => ['dot' => 'bg-amber-500', 'text' => 'text-amber-700'],
            'red' => ['dot' => 'bg-rose-500', 'text' => 'text-rose-700'],
            'purple' => ['dot' => 'bg-violet-500', 'text' => 'text-violet-700'],
            'blue' => ['dot' => 'bg-blue-500', 'text' => 'text-blue-700'],
            'green' => ['dot' => 'bg-emerald-500', 'text' => 'text-emerald-700'],
        ];
    @endphp

    <section class="space-y-4">
        {{-- Header --}}
        <header class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h1 class="text-lg font-bold tracking-tight text-gray-900 sm:text-xl">
                    سلام {{ $user->name }}
                </h1>
                <p class="mt-1 text-[11px] leading-5 text-gray-500">
                    وضعیت عملیاتی امروز {{ $companyName }}
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <span class="inline-flex h-9 items-center rounded-lg border border-gray-200 bg-white px-3 text-[11px] font-medium text-gray-500">
                    {{ $today }}
                </span>

                @can('customers.create')
                    <a href="{{ route('customers.create') }}" class="inline-flex h-9 items-center rounded-lg border border-gray-200 bg-white px-3 text-[11px] font-medium text-gray-700 transition hover:border-gray-300 hover:bg-gray-50">
                        مشتری جدید
                    </a>
                @endcan

                @can('tasks.create')
                    <a href="{{ route('tasks.create') }}" class="inline-flex h-9 items-center rounded-lg bg-emerald-400 px-3 text-[11px] font-bold text-gray-900 transition hover:bg-emerald-300">
                        تسک جدید
                    </a>
                @endcan
            </div>
        </header>

        {{-- Compact KPI row --}}
        <section class="grid gap-2 sm:grid-cols-2 xl:grid-cols-4">
            @can('tickets.view')
                <x-stat-card label="نیازمند پاسخ" :value="number_format($ticketMetrics['needsResponse'])" :description="number_format($ticketMetrics['open']).' تیکت باز'" :href="route('tickets.index')" tone="warning" />
            @endcan

            @can('tasks.view')
                <x-stat-card :label="$canViewAllTasks ? 'تسک‌های معوق تیم' : 'تسک‌های معوق من'" :value="number_format($canViewAllTasks ? $taskMetrics['teamOverdue'] : $taskMetrics['overdue'])" description="نیازمند پیگیری" :href="route('tasks.index', ['quick' => 'overdue'])" tone="danger" />
            @endcan

            @can('finance.view')
                <x-stat-card label="فیش منتظر بررسی" :value="number_format($financeMetrics['pendingReceipts'])" :description="number_format($financeMetrics['pendingAmount']).' تومان'" :href="route('finance.receipts.index')" tone="violet" data-testid="dashboard-finance-pending" data-count="{{ $financeMetrics['pendingReceipts'] }}" />
            @endcan

            @if($canViewAllTickets)
                <x-stat-card label="تیکت بدون مسئول" :value="number_format($ticketMetrics['unassigned'])" description="نیازمند تخصیص" :href="route('tickets.index', ['assignee_id' => 'unassigned'])" tone="info" data-testid="dashboard-ticket-unassigned" data-count="{{ $ticketMetrics['unassigned'] }}" />
            @elseif(auth()->user()->can('customers.view'))
                <x-stat-card label="مشتریان فعال" :value="number_format($activeCustomers)" description="حساب فعال" :href="route('customers.index')" tone="success" />
            @endif
        </section>

        {{-- Primary operational area --}}
        <div class="grid gap-3 xl:grid-cols-[minmax(0,1.35fr)_minmax(320px,.8fr)]">
            <section data-testid="dashboard-action-items" class="overflow-hidden rounded-xl border border-gray-200 bg-white">
                <div class="flex min-h-14 items-center justify-between border-b border-gray-100 px-4">
                    <div>
                        <h2 class="text-xs font-bold text-gray-900">نیازمند اقدام</h2>
                        <p class="mt-0.5 text-[9px] text-gray-400">مواردی که بهتر است زودتر بررسی شوند</p>
                    </div>
                </div>

                @forelse($actionItems as $item)
                    @php($tone = $actionTone[$item['tone']] ?? $actionTone['green'])

                    <a href="{{ $item['url'] }}" class="grid gap-2 border-b border-gray-100 px-4 py-3 last:border-0 hover:bg-gray-50/70 sm:grid-cols-[10px_minmax(0,1fr)_auto] sm:items-center">
                        <span class="hidden h-2 w-2 rounded-full {{ $tone['dot'] }} sm:block"></span>

                        <div class="min-w-0">
                            <strong class="block truncate text-[11px] font-semibold text-gray-800">{{ $item['title'] }}</strong>
                            <span class="mt-1 block truncate text-[9px] text-gray-400">{{ $item['description'] }}</span>
                        </div>

                        <div class="text-right sm:text-left">
                            <strong class="text-[9px] font-semibold {{ $tone['text'] }}">{{ $item['action'] }} ←</strong>
                            <span class="mr-2 text-[8px] text-gray-400 sm:mr-0 sm:mt-1 sm:block">{{ $item['time'] }}</span>
                        </div>
                    </a>
                @empty
                    <div class="px-4 py-9 text-center">
                        <strong class="block text-xs text-gray-700">مورد فوری وجود ندارد</strong>
                        <p class="mt-1 text-[10px] text-gray-400">در حال حاضر مورد خاصی نیازمند اقدام نیست.</p>
                    </div>
                @endforelse
            </section>

            @can('tasks.view')
                <section class="overflow-hidden rounded-xl border border-gray-200 bg-white">
                    <div class="flex min-h-14 items-center justify-between border-b border-gray-100 px-4">
                        <div>
                            <h2 class="text-xs font-bold text-gray-900">تسک‌ها</h2>
                            <p class="mt-0.5 text-[9px] text-gray-400">نمای سریع وضعیت کارها</p>
                        </div>
                        <a href="{{ route('tasks.index') }}" class="text-[9px] font-semibold text-emerald-700">Kanban ←</a>
                    </div>

                    <div class="grid grid-cols-4 divide-x divide-x-reverse divide-gray-100 border-b border-gray-100">
                        <div class="px-2 py-3 text-center">
                            <strong class="block text-sm text-gray-800">{{ number_format($taskStatusMetrics[\App\Enums\TaskStatus::New->value] ?? 0) }}</strong>
                            <span class="mt-1 block text-[8px] text-gray-400">جدید</span>
                        </div>
                        <div class="px-2 py-3 text-center">
                            <strong class="block text-sm text-gray-800">{{ number_format($taskStatusMetrics[\App\Enums\TaskStatus::Pending->value] ?? 0) }}</strong>
                            <span class="mt-1 block text-[8px] text-gray-400">در انتظار</span>
                        </div>
                        <div class="px-2 py-3 text-center">
                            <strong class="block text-sm text-gray-800">{{ number_format($taskStatusMetrics[\App\Enums\TaskStatus::InProgress->value] ?? 0) }}</strong>
                            <span class="mt-1 block text-[8px] text-gray-400">در حال انجام</span>
                        </div>
                        <div class="px-2 py-3 text-center">
                            <strong class="block text-sm text-gray-800">{{ number_format($taskStatusMetrics[\App\Enums\TaskStatus::Review->value] ?? 0) }}</strong>
                            <span class="mt-1 block text-[8px] text-gray-400">بررسی</span>
                        </div>
                    </div>

                    <div class="px-4 py-1">
                        @if($canViewAllTasks)
                            <div class="flex items-center justify-between border-b border-gray-100 py-2 text-[9px] text-gray-400">
                                <span data-testid="dashboard-task-team-open" data-count="{{ $taskMetrics['teamOpen'] }}">تسک باز: <strong class="text-gray-700">{{ number_format($taskMetrics['teamOpen']) }}</strong></span>
                                <span data-testid="dashboard-task-team-overdue" data-count="{{ $taskMetrics['teamOverdue'] }}">معوق: <strong class="text-rose-700">{{ number_format($taskMetrics['teamOverdue']) }}</strong></span>
                            </div>

                            @forelse($priorityTasks as $task)
                                <a href="{{ route('tasks.show', $task) }}" class="flex items-center justify-between gap-3 border-b border-dashed border-gray-100 py-2.5 last:border-0">
                                    <div class="min-w-0">
                                        <strong class="block truncate text-[10px] font-semibold text-gray-700">{{ $task->title }}</strong>
                                        <span class="mt-1 block truncate text-[8px] text-gray-400">
                                            {{ $task->assignee?->name ?? 'بدون مسئول' }} ·
                                            {{ $task->due_date ? app(\App\Support\DatePresenter::class)->date($task->due_date) : 'بدون ددلاین' }}
                                        </span>
                                    </div>
                                    <x-badge :type="$task->priority->intent()">{{ $task->priority->label() }}</x-badge>
                                </a>
                            @empty
                                <p class="py-6 text-center text-[10px] text-gray-400">تسک بازی وجود ندارد.</p>
                            @endforelse
                        @else
                            <div class="grid grid-cols-3 divide-x divide-x-reverse divide-gray-100 py-3 text-center">
                                <div data-testid="dashboard-task-today" data-count="{{ $taskMetrics['today'] }}"><strong class="block text-sm text-gray-800">{{ number_format($taskMetrics['today']) }}</strong><span class="text-[8px] text-gray-400">امروز</span></div>
                                <div data-testid="dashboard-task-overdue" data-count="{{ $taskMetrics['overdue'] }}"><strong class="block text-sm text-rose-700">{{ number_format($taskMetrics['overdue']) }}</strong><span class="text-[8px] text-gray-400">معوق</span></div>
                                <div data-testid="dashboard-task-in-progress" data-count="{{ $taskMetrics['inProgress'] }}"><strong class="block text-sm text-blue-700">{{ number_format($taskMetrics['inProgress']) }}</strong><span class="text-[8px] text-gray-400">در حال انجام</span></div>
                            </div>

                            @forelse($todayTasks as $task)
                                <a href="{{ route('tasks.show', $task) }}" class="flex items-center justify-between gap-3 border-t border-dashed border-gray-100 py-2.5">
                                    <strong class="min-w-0 truncate text-[10px] font-semibold text-gray-700">{{ $task->title }}</strong>
                                    <x-badge :type="$task->priority->intent()">{{ $task->priority->label() }}</x-badge>
                                </a>
                            @empty
                                <p class="py-6 text-center text-[10px] text-gray-400">برای امروز تسکی ندارید.</p>
                            @endforelse
                        @endif
                    </div>
                </section>
            @endcan
        </div>

        {{-- Secondary panels --}}
        <div class="grid gap-3 xl:grid-cols-2">
            @can('tickets.view')
                <section class="overflow-hidden rounded-xl border border-gray-200 bg-white">
                    <div class="flex min-h-14 items-center justify-between border-b border-gray-100 px-4">
                        <div>
                            <h2 class="text-xs font-bold text-gray-900">پشتیبانی مشتریان</h2>
                            <p class="mt-0.5 text-[9px] text-gray-400">وضعیت تیکت‌های جاری</p>
                        </div>
                        <a href="{{ route('tickets.index') }}" class="text-[9px] font-semibold text-emerald-700">همه تیکت‌ها ←</a>
                    </div>

                    <div class="grid grid-cols-4 divide-x divide-x-reverse divide-gray-100 border-b border-gray-100">
                        <div class="px-2 py-3 text-center">
                            <strong class="block text-sm text-amber-700">{{ number_format($ticketMetrics['needsResponse']) }}</strong>
                            <span class="mt-1 block text-[8px] text-gray-400">نیازمند پاسخ</span>
                        </div>
                        <div class="px-2 py-3 text-center">
                            <strong class="block text-sm text-blue-700">{{ number_format($ticketMetrics['inProgress']) }}</strong>
                            <span class="mt-1 block text-[8px] text-gray-400">در حال انجام</span>
                        </div>
                        <div class="px-2 py-3 text-center">
                            <strong class="block text-sm text-gray-800">{{ number_format($ticketMetrics['waiting']) }}</strong>
                            <span class="mt-1 block text-[8px] text-gray-400">منتظر مشتری</span>
                        </div>
                        <div class="px-2 py-3 text-center">
                            <strong class="block text-sm text-gray-800">{{ number_format($ticketMetrics['open']) }}</strong>
                            <span class="mt-1 block text-[8px] text-gray-400">باز</span>
                        </div>
                    </div>

                    <div class="px-4 py-1">
                        @forelse($attentionTickets as $ticket)
                            <a href="{{ route('tickets.show', $ticket) }}" class="flex items-center justify-between gap-3 border-b border-dashed border-gray-100 py-2.5 last:border-0">
                                <div class="min-w-0">
                                    <strong class="block truncate text-[10px] font-semibold text-gray-700">#{{ $ticket->id }} · {{ $ticket->subject }}</strong>
                                    <span class="mt-1 block truncate text-[8px] text-gray-400">{{ $ticket->customer?->name ?? 'مشتری حذف‌شده' }}</span>
                                </div>
                                <div class="flex shrink-0 gap-1">
                                    <x-badge :type="$ticket->priority->intent()">{{ $ticket->priority->label() }}</x-badge>
                                    <x-badge :type="$ticket->status->intent()">{{ $ticket->status->label() }}</x-badge>
                                </div>
                            </a>
                        @empty
                            <p class="py-6 text-center text-[10px] text-gray-400">تیکت نیازمند توجهی وجود ندارد.</p>
                        @endforelse
                    </div>
                </section>
            @endcan

            @can('finance.view')
                <section data-testid="dashboard-finance-panel" class="overflow-hidden rounded-xl border border-gray-200 bg-white">
                    <div class="flex min-h-14 items-center justify-between border-b border-gray-100 px-4">
                        <div>
                            <h2 class="text-xs font-bold text-gray-900">مالی</h2>
                            <p class="mt-0.5 text-[9px] text-gray-400">خلاصه مطالبات و پرداخت‌ها</p>
                        </div>
                        <a href="{{ route('finance.index') }}" class="text-[9px] font-semibold text-emerald-700">گزارش مالی ←</a>
                    </div>

                    <div class="grid grid-cols-2 divide-x divide-x-reverse divide-gray-100 border-b border-gray-100 sm:grid-cols-4">
                        <div class="px-3 py-3.5">
                            <span class="block text-[8px] text-gray-400">فیش منتظر</span>
                            <strong class="mt-1.5 block text-sm text-gray-800">{{ number_format($financeMetrics['pendingReceipts']) }} مورد</strong>
                        </div>
                        <div class="px-3 py-3.5">
                            <span class="block text-[8px] text-gray-400">مشتریان بدهکار</span>
                            <strong class="mt-1.5 block text-sm text-gray-800">{{ number_format($financeMetrics['debtors']) }}</strong>
                        </div>
                        <div class="px-3 py-3.5">
                            <span class="block text-[8px] text-gray-400">مجموع مطالبات</span>
                            <strong class="mt-1.5 block text-sm text-gray-800">{{ number_format($financeMetrics['claims']) }}</strong>
                            <span class="mt-1 block text-[8px] text-gray-400">تومان</span>
                        </div>
                        <div class="px-3 py-3.5">
                            <span class="block text-[8px] text-gray-400">تأییدشده امروز</span>
                            <strong class="mt-1.5 block text-sm text-gray-800">{{ number_format($financeMetrics['approvedTodayCount']) }} مورد</strong>
                        </div>
                    </div>

                    @if($topDebtors->isNotEmpty())
                        <div class="px-4 py-1">
                            @foreach($topDebtors as $row)
                                @if($row['customer'])
                                    <a href="{{ route('finance.customers.show', $row['customer']) }}" class="flex items-center justify-between gap-3 border-b border-dashed border-gray-100 py-2.5 last:border-0">
                                        <strong class="truncate text-[10px] font-medium text-gray-700">{{ $row['customer']->name }}</strong>
                                        <span class="shrink-0 text-[9px] font-semibold text-rose-700">{{ number_format($row['balance']) }} تومان</span>
                                    </a>
                                @endif
                            @endforeach
                        </div>
                    @endif
                </section>
            @endcan
        </div>

        {{-- Activity --}}
        @can('activity.view')
            <section class="overflow-hidden rounded-xl border border-gray-200 bg-white">
                <div class="flex min-h-14 items-center justify-between border-b border-gray-100 px-4">
                    <div>
                        <h2 class="text-xs font-bold text-gray-900">آخرین فعالیت‌ها</h2>
                        <p class="mt-0.5 text-[9px] text-gray-400">آخرین تغییرات مهم ثبت‌شده در سیستم</p>
                    </div>
                    <a href="{{ route('activity.index') }}" class="text-[9px] font-semibold text-emerald-700">گزارش فعالیت‌ها ←</a>
                </div>

                <div class="divide-y divide-gray-100 px-4">
                    @forelse($recentActivity as $activity)
                        <a href="{{ route('activity.show', $activity) }}" class="grid gap-1.5 py-2.5 hover:bg-gray-50/60 sm:grid-cols-[105px_minmax(0,1fr)] sm:items-center">
                            <span class="text-[8px] text-gray-400">{{ app(\App\Support\DatePresenter::class)->dateTime($activity->created_at) }}</span>
                            <div class="min-w-0 truncate text-[9px] leading-5 text-gray-500">
                                <strong class="font-semibold text-gray-700">{{ $activity->actor_name ?: 'سیستم' }}</strong>
                                <span> · {{ $activity->description ?: \App\Support\ActivityCatalog::eventLabel($activity->event) }}</span>
                            </div>
                        </a>
                    @empty
                        <p class="py-7 text-center text-[10px] text-gray-400">هنوز فعالیتی ثبت نشده است.</p>
                    @endforelse
                </div>
            </section>
        @endcan

        {{-- Low-priority system counters --}}
        <section class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
            @can('customers.view')
                <a href="{{ route('customers.index') }}" class="flex items-center justify-between rounded-lg border border-gray-200 bg-white px-4 py-2.5 text-[10px] text-gray-500 transition hover:border-gray-300">
                    <span>مشتریان فعال</span>
                    <strong class="text-xs text-gray-800">{{ number_format($activeCustomers) }}</strong>
                </a>
            @endcan

            @can('team.view')
                <a href="{{ route('team.index') }}" class="flex items-center justify-between rounded-lg border border-gray-200 bg-white px-4 py-2.5 text-[10px] text-gray-500 transition hover:border-gray-300">
                    <span>اعضای فعال</span>
                    <strong class="text-xs text-gray-800">{{ number_format($activeUsers) }}</strong>
                </a>
            @endcan

            @can('roles.view')
                <a href="{{ route('roles.index') }}" class="flex items-center justify-between rounded-lg border border-gray-200 bg-white px-4 py-2.5 text-[10px] text-gray-500 transition hover:border-gray-300">
                    <span>نقش‌های تعریف‌شده</span>
                    <strong class="text-xs text-gray-800">{{ number_format($rolesCount) }}</strong>
                </a>
            @endcan
        </section>
    </section>
</x-layouts.app>
