<x-layouts.app title="تسک‌ها">
    @if($view === 'board')
        <div
            x-data="jaheshTaskBoard(
                @js(route('tasks.status.update', ['task' => '__TASK__'])),
                @js(route('tasks.store')),
                @js(csrf_token()),
                @js($boardCounts->all()),
                @js($mobileStatus),
                @js(\App\Enums\TaskStatus::workflowValues()),
                @js($statusLabels),
                @js(
                    $search !== ''
                    || $quick !== 'all'
                    || $priority !== null
                    || $status !== null
                    || $customerId !== null
                    || $assigneeId !== null
                )
            )"
            @keydown.escape.window="quickOpen = false"
        >
            <x-page-header
                :title="$scope === 'all' ? 'همه تسک‌ها' : 'تسک‌های من'"
                description="کارهای روزانه را در چهار مرحله اصلی مدیریت کنید و وضعیت را با درگ‌ودراپ تغییر دهید."
            >
                <x-slot:actions>
                    @can('tasks.create')
                        <button type="button" class="btn btn-primary" @click="openQuickCreate('new')">+ تسک جدید</button>
                    @endcan
                </x-slot:actions>
            </x-page-header>

            <div class="mb-4 flex flex-col gap-3 xl:flex-row xl:items-center xl:justify-between">
                @can('tasks.view_all')
                    <div class="flex gap-1 border-b border-gray-200">
                        <a
                            href="{{ route('tasks.index', array_merge(request()->except(['scope', 'page']), ['scope' => 'mine'])) }}"
                            class="border-b-2 px-3 py-2 text-sm font-semibold {{ $scope === 'mine' ? 'border-emerald-500 text-emerald-700' : 'border-transparent text-gray-500 hover:text-gray-800' }}"
                        >تسک‌های من</a>
                        <a
                            href="{{ route('tasks.index', array_merge(request()->except(['scope', 'page']), ['scope' => 'all'])) }}"
                            class="border-b-2 px-3 py-2 text-sm font-semibold {{ $scope === 'all' ? 'border-emerald-500 text-emerald-700' : 'border-transparent text-gray-500 hover:text-gray-800' }}"
                        >همه تسک‌ها</a>
                    </div>
                @endcan

                <div class="flex flex-wrap items-center gap-2">
                    <div class="flex rounded-lg border border-gray-200 bg-white p-1">
                        <a
                            href="{{ route('tasks.index', array_merge(request()->except(['view', 'page', 'status']), ['view' => 'board'])) }}"
                            class="rounded-md bg-emerald-50 px-4 py-2 text-sm font-semibold text-emerald-800"
                        >برد</a>
                        <a
                            href="{{ route('tasks.index', array_merge(request()->except(['view', 'page']), ['view' => 'list'])) }}"
                            class="rounded-md px-4 py-2 text-sm font-semibold text-gray-500 hover:text-gray-800"
                        >لیست</a>
                    </div>
                </div>
            </div>

            <div class="mb-4 flex flex-col gap-2 lg:flex-row lg:items-center lg:justify-between">
                <div class="flex gap-2 overflow-x-auto pb-1">
                    @foreach(['all' => 'همه', 'today' => 'امروز', 'overdue' => 'عقب‌افتاده'] as $value => $label)
                        <a
                            href="{{ route('tasks.index', array_merge(request()->except(['quick', 'page']), $value === 'all' ? [] : ['quick' => $value])) }}"
                            class="btn shrink-0 {{ $quick === $value ? 'btn-primary' : 'btn-secondary' }}"
                        >{{ $label }}</a>
                    @endforeach
                </div>

                <div class="flex gap-2 overflow-x-auto pb-1 text-xs">
                    @foreach($secondaryStatuses as $secondaryStatus)
                        <a
                            href="{{ route('tasks.index', array_merge(request()->except(['view', 'status', 'page', 'quick']), ['view' => 'list', 'status' => $secondaryStatus->value])) }}"
                            class="inline-flex min-h-10 shrink-0 items-center gap-2 rounded-lg border border-gray-200 bg-white px-3 font-semibold text-gray-600 transition hover:border-gray-300 hover:text-gray-900"
                        >
                            <span>{{ $secondaryStatus->label() }}</span>
                            <span
                                class="grid min-w-6 place-items-center rounded-full bg-gray-100 px-1.5 py-0.5 text-[11px]"
                                x-text="count('{{ $secondaryStatus->value }}')"
                            >{{ $boardCounts[$secondaryStatus->value] ?? 0 }}</span>
                        </a>
                    @endforeach
                </div>
            </div>

            <form
                method="GET"
                class="panel mb-4 p-2.5"
                x-data="{ filtersOpen: {{ request()->hasAny(['assignee_id', 'customer_id', 'priority']) ? 'true' : 'false' }} }"
            >
                <input type="hidden" name="view" value="board">
                @if($scope === 'all')<input type="hidden" name="scope" value="all">@endif
                @if($quick !== 'all')<input type="hidden" name="quick" value="{{ $quick }}">@endif

                <div class="flex items-center gap-2">
                    <label for="task-search" class="sr-only">جستجوی تسک</label>
                    <input
                        id="task-search"
                        class="form-control min-w-0 flex-1"
                        name="q"
                        value="{{ $search }}"
                        placeholder="جستجو در تسک‌ها..."
                    >
                    <button type="submit" class="btn btn-secondary shrink-0 px-3" aria-label="جستجو">
                        <span class="hidden sm:inline">جستجو</span>
                        <span class="sm:hidden">⌕</span>
                    </button>
                    <button
                        type="button"
                        class="btn btn-secondary shrink-0 px-3"
                        @click="filtersOpen = !filtersOpen"
                        :aria-expanded="filtersOpen"
                    >فیلتر</button>
                </div>

                <div
                    x-cloak
                    x-show="filtersOpen"
                    x-transition
                    class="mt-2 grid gap-2 border-t border-gray-100 pt-2 sm:grid-cols-2 lg:grid-cols-4"
                >
                    @can('tasks.view_all')
                        <select name="assignee_id" class="form-control">
                            <option value="">همه مسئول‌ها</option>
                            @foreach($assignees as $assignee)
                                <option value="{{ $assignee->id }}" @selected($assigneeId === $assignee->id)>{{ $assignee->name }}</option>
                            @endforeach
                        </select>
                    @endcan

                    <select name="customer_id" class="form-control">
                        <option value="">همه مشتری‌ها</option>
                        @foreach($customers as $customer)
                            <option value="{{ $customer->id }}" @selected($customerId === $customer->id)>{{ $customer->name }}</option>
                        @endforeach
                    </select>

                    <select name="priority" class="form-control">
                        <option value="">همه اولویت‌ها</option>
                        @foreach($priorities as $item)
                            <option value="{{ $item->value }}" @selected($priority === $item->value)>{{ $item->label() }}</option>
                        @endforeach
                    </select>

                    <div class="flex gap-2">
                        <button type="submit" class="btn btn-primary flex-1">اعمال</button>
                        <a
                            href="{{ route('tasks.index', array_filter(['view' => 'board', 'scope' => $scope === 'all' ? 'all' : null])) }}"
                            class="btn btn-secondary flex-1"
                        >پاک کردن</a>
                    </div>
                </div>
            </form>

            {{-- دسکتاپ: چهار مرحله اصلی بدون اسکرول افقی --}}
            <div class="hidden gap-3 md:grid md:grid-cols-2 xl:grid-cols-4">
                @foreach($workflowStatuses as $columnStatus)
                    @php($columnTasks = $boardTasks->get($columnStatus->value, collect()))

                    <section
                        class="flex min-h-[420px] min-w-0 flex-col rounded-xl border border-gray-200 bg-gray-50/70 transition"
                        data-task-column="{{ $columnStatus->value }}"
                        :class="dragOverStatus === '{{ $columnStatus->value }}' ? 'border-emerald-300 bg-emerald-50/70 ring-2 ring-emerald-200' : ''"
                        @dragenter.prevent="dragOverStatus = '{{ $columnStatus->value }}'"
                        @dragover.prevent="dragOverStatus = '{{ $columnStatus->value }}'"
                        @dragleave="if ($event.currentTarget === $event.target) dragOverStatus = null"
                        @drop.prevent="dropTask('{{ $columnStatus->value }}')"
                    >
                        <header class="flex items-center justify-between gap-2 border-b border-gray-200 px-3 py-3">
                            <div class="flex min-w-0 items-center gap-2">
                                <span class="h-2.5 w-2.5 shrink-0 rounded-full {{ match($columnStatus->value) {
                                    'in_progress' => 'bg-sky-500',
                                    'review' => 'bg-amber-500',
                                    'pending' => 'bg-slate-400',
                                    default => 'bg-gray-400',
                                } }}"></span>
                                <strong class="truncate text-sm">{{ $columnStatus->label() }}</strong>
                            </div>

                            <span
                                class="grid min-w-7 place-items-center rounded-full bg-white px-2 py-1 text-xs font-bold text-gray-600"
                                x-text="count('{{ $columnStatus->value }}')"
                            >{{ $boardCounts[$columnStatus->value] ?? 0 }}</span>
                        </header>

                        <div
                            class="flex-1 space-y-2 p-2.5"
                            data-task-cards="{{ $columnStatus->value }}"
                            data-board-mode="desktop"
                        >
                            @foreach($columnTasks as $task)
                                @include('tasks._card', [
                                    'task' => $task,
                                    'scope' => $scope,
                                    'statuses' => $statuses,
                                    'mode' => 'desktop',
                                ])
                            @endforeach
                        </div>

                        @can('tasks.create')
                            <div class="border-t border-gray-200 p-2.5">
                                <button
                                    type="button"
                                    class="flex min-h-10 w-full items-center justify-center rounded-lg border border-dashed border-gray-300 bg-white/70 px-3 text-xs font-semibold text-gray-500 transition hover:border-emerald-300 hover:bg-emerald-50 hover:text-emerald-800"
                                    @click="openQuickCreate('{{ $columnStatus->value }}')"
                                >+ افزودن تسک</button>
                            </div>
                        @endcan
                    </section>
                @endforeach
            </div>

            {{-- موبایل: یک مرحله در هر لحظه، بدون اسکرول افقی برد --}}
            <div class="md:hidden">
                <div class="mb-3 grid grid-cols-4 gap-1 rounded-xl border border-gray-200 bg-white p-1">
                    @foreach($workflowStatuses as $columnStatus)
                        <button
                            type="button"
                            class="min-w-0 rounded-lg px-1.5 py-2 text-center text-[11px] font-semibold transition"
                            :class="mobileStatus === '{{ $columnStatus->value }}'
                                ? 'bg-emerald-50 text-emerald-800'
                                : 'text-gray-500'"
                            @click="mobileStatus = '{{ $columnStatus->value }}'"
                        >
                            <span class="block truncate">{{ $columnStatus->label() }}</span>
                            <span
                                class="mt-1 inline-grid min-w-5 place-items-center rounded-full bg-gray-100 px-1 text-[10px] text-gray-600"
                                x-text="count('{{ $columnStatus->value }}')"
                            >{{ $boardCounts[$columnStatus->value] ?? 0 }}</span>
                        </button>
                    @endforeach
                </div>

                @foreach($workflowStatuses as $columnStatus)
                    @php($columnTasks = $boardTasks->get($columnStatus->value, collect()))

                    <section
                        x-cloak
                        x-show="mobileStatus === '{{ $columnStatus->value }}'"
                        x-transition.opacity
                        class="overflow-hidden rounded-xl border border-gray-200 bg-gray-50/70"
                    >
                        <header class="flex items-center justify-between border-b border-gray-200 px-3 py-3">
                            <strong class="text-sm">{{ $columnStatus->label() }}</strong>
                            <span class="text-xs text-gray-500" x-text="count('{{ $columnStatus->value }}') + ' تسک'"></span>
                        </header>

                        <div
                            class="min-h-40 space-y-2 p-2.5"
                            data-task-cards="{{ $columnStatus->value }}"
                            data-board-mode="mobile"
                        >
                            @foreach($columnTasks as $task)
                                @include('tasks._card', [
                                    'task' => $task,
                                    'scope' => $scope,
                                    'statuses' => $statuses,
                                    'mode' => 'mobile',
                                ])
                            @endforeach
                        </div>

                        @can('tasks.create')
                            <div class="border-t border-gray-200 p-2.5">
                                <button
                                    type="button"
                                    class="btn btn-secondary w-full"
                                    @click="openQuickCreate('{{ $columnStatus->value }}')"
                                >+ افزودن تسک در این مرحله</button>
                            </div>
                        @endcan
                    </section>
                @endforeach
            </div>

            {{-- مقصدهای ثانویه هنگام درگ در دسکتاپ --}}
            <template x-if="dragging">
                <div class="fixed bottom-6 left-1/2 z-40 hidden w-[min(720px,calc(100vw-2rem))] -translate-x-1/2 grid-cols-3 gap-2 rounded-lg border border-gray-200 bg-white p-2.5 shadow-sm md:grid">
                    @foreach($secondaryStatuses as $secondaryStatus)
                        <div
                            class="flex min-h-14 items-center justify-center rounded-xl border border-dashed px-3 text-sm font-bold transition {{ match($secondaryStatus->value) {
                                'completed' => 'border-emerald-300 text-emerald-700',
                                'paused' => 'border-amber-300 text-amber-700',
                                'cancelled' => 'border-red-200 text-red-600',
                                default => 'border-gray-300 text-gray-600',
                            } }}"
                            :class="dragOverStatus === '{{ $secondaryStatus->value }}' ? 'bg-gray-100 ring-2 ring-gray-200' : 'bg-white'"
                            @dragenter.prevent="dragOverStatus = '{{ $secondaryStatus->value }}'"
                            @dragover.prevent="dragOverStatus = '{{ $secondaryStatus->value }}'"
                            @drop.prevent="dropTask('{{ $secondaryStatus->value }}')"
                        >
                            {{ $secondaryStatus->label() }}
                        </div>
                    @endforeach
                </div>
            </template>

            <div
                x-cloak
                x-show="notice"
                x-transition
                class="fixed left-4 top-4 z-[70] max-w-sm rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm font-semibold shadow-lg"
                x-text="notice"
            ></div>

            {{-- ایجاد سریع تسک --}}
            @can('tasks.create')
                <div
                    x-cloak
                    x-show="quickOpen"
                    x-transition.opacity
                    class="fixed inset-0 z-[60] flex items-end justify-center bg-black/30 p-0 sm:items-center sm:p-4"
                    @click.self="closeQuickCreate()"
                >
                    <div
                        x-transition
                        class="max-h-[90vh] w-full overflow-y-auto rounded-t-lg bg-white p-4 shadow-sm sm:max-w-lg sm:rounded-lg sm:p-5"
                    >
                        <div class="mb-4 flex items-start justify-between gap-3">
                            <div>
                                <h2 class="text-base font-bold">تسک جدید</h2>
                                <p class="mt-1 text-xs text-gray-500">
                                    وضعیت:
                                    <span class="font-semibold text-gray-700" x-text="statusLabel(quickStatus)"></span>
                                </p>
                            </div>
                            <button
                                type="button"
                                class="grid h-10 w-10 place-items-center rounded-lg text-xl text-gray-400 hover:bg-gray-100 hover:text-gray-700"
                                @click="closeQuickCreate()"
                                aria-label="بستن"
                            >×</button>
                        </div>

                        <form x-ref="quickForm" action="{{ route('tasks.store') }}" method="POST" @submit.prevent="submitQuickCreate($event)" class="space-y-3">
                            @csrf
                            <input type="hidden" name="status" :value="quickStatus">
                            <input type="hidden" name="scope" value="{{ $scope }}">

                            <div>
                                <label for="quick-title" class="form-label">عنوان <span class="text-red-600">*</span></label>
                                <input
                                    id="quick-title"
                                    x-ref="quickTitle"
                                    name="title"
                                    class="form-control"
                                    maxlength="255"
                                    required
                                    autocomplete="off"
                                    placeholder="مثلاً اصلاح صفحه محصول"
                                >
                                <template x-if="quickErrors.title">
                                    <p class="form-error" x-text="quickErrors.title[0]"></p>
                                </template>
                            </div>

                            <div class="grid gap-3 sm:grid-cols-2">
                                @if($canAssign)
                                    <div>
                                        <label for="quick-assignee" class="form-label">مسئول</label>
                                        <select id="quick-assignee" name="assignee_id" class="form-control" required>
                                            @foreach($assignees as $assignee)
                                                <option value="{{ $assignee->id }}" @selected($assignee->id === auth()->id())>{{ $assignee->name }}</option>
                                            @endforeach
                                        </select>
                                        <template x-if="quickErrors.assignee_id">
                                            <p class="form-error" x-text="quickErrors.assignee_id[0]"></p>
                                        </template>
                                    </div>
                                @else
                                    <input type="hidden" name="assignee_id" value="{{ auth()->id() }}">
                                    <div>
                                        <label class="form-label">مسئول</label>
                                        <div class="form-control flex items-center bg-gray-50 text-gray-600">{{ auth()->user()->name }}</div>
                                    </div>
                                @endif

                                <div>
                                    <label for="quick-customer" class="form-label">مشتری <span class="font-normal text-gray-400">(اختیاری)</span></label>
                                    <select id="quick-customer" name="customer_id" class="form-control">
                                        <option value="">بدون مشتری</option>
                                        @foreach($customers as $customer)
                                            <option value="{{ $customer->id }}">{{ $customer->name }}{{ $customer->company_name ? ' — '.$customer->company_name : '' }}</option>
                                        @endforeach
                                    </select>
                                    <template x-if="quickErrors.customer_id">
                                        <p class="form-error" x-text="quickErrors.customer_id[0]"></p>
                                    </template>
                                </div>

                                <div>
                                    <label for="quick-priority" class="form-label">اولویت</label>
                                    <select id="quick-priority" name="priority" class="form-control">
                                        @foreach($priorities as $item)
                                            <option value="{{ $item->value }}" @selected($item === \App\Enums\TaskPriority::Normal)>{{ $item->label() }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <x-persian-date-input label="ددلاین" name="due_date" />
                            </div>

                            <template x-if="quickErrors.due_date">
                                <p class="form-error" x-text="quickErrors.due_date[0]"></p>
                            </template>

                            <div class="flex flex-col-reverse gap-2 border-t border-gray-100 pt-4 sm:flex-row sm:items-center sm:justify-between">
                                <a
                                    class="text-center text-sm font-semibold text-gray-500 hover:text-gray-800"
                                    :href="@js(route('tasks.create')) + '?status=' + quickStatus"
                                >فرم کامل تسک</a>

                                <div class="flex gap-2">
                                    <button type="button" class="btn btn-secondary flex-1 sm:flex-none" @click="closeQuickCreate()">انصراف</button>
                                    <button type="submit" class="btn btn-primary flex-1 sm:flex-none" :disabled="creating">
                                        <span x-show="!creating">ایجاد تسک</span>
                                        <span x-cloak x-show="creating">در حال ثبت...</span>
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            @endcan

            <script>
                window.jaheshTaskBoard = function (
                    statusUrlTemplate,
                    storeUrl,
                    csrfToken,
                    initialCounts,
                    initialMobileStatus,
                    workflowStatuses,
                    statusLabels,
                    filteredBoard
                ) {
                    return {
                        counts: { ...initialCounts },
                        mobileStatus: initialMobileStatus,
                        workflowStatuses,
                        statusLabels,
                        filteredBoard,
                        draggedTask: null,
                        dragArmed: null,
                        dragging: false,
                        dragOverStatus: null,
                        quickOpen: false,
                        quickStatus: 'new',
                        quickErrors: {},
                        creating: false,
                        notice: '',
                        noticeTimer: null,

                        count(status) {
                            return Number(this.counts[status] ?? 0);
                        },

                        statusLabel(status) {
                            return this.statusLabels[status] ?? status;
                        },

                        showNotice(message) {
                            this.notice = message;
                            window.clearTimeout(this.noticeTimer);
                            this.noticeTimer = window.setTimeout(() => {
                                this.notice = '';
                            }, 2600);
                        },

                        openQuickCreate(status = 'new') {
                            this.quickStatus = this.workflowStatuses.includes(status) ? status : 'new';
                            this.quickErrors = {};
                            this.quickOpen = true;

                            this.$nextTick(() => {
                                this.$refs.quickTitle?.focus();
                            });
                        },

                        closeQuickCreate() {
                            this.quickOpen = false;
                            this.quickErrors = {};
                        },

                        startDrag(event, taskId, status) {
                            const card = event.currentTarget;

                            if (this.dragArmed !== taskId) {
                                event.preventDefault();
                                return;
                            }

                            this.draggedTask = { id: taskId, status };
                            this.dragging = true;
                            this.dragOverStatus = status;

                            event.dataTransfer.effectAllowed = 'move';
                            event.dataTransfer.setData('text/plain', String(taskId));
                            card.classList.add('opacity-60');
                        },

                        endDrag(event) {
                            event.currentTarget?.classList.remove('opacity-60');
                            this.dragArmed = null;

                            window.setTimeout(() => {
                                this.dragging = false;
                                this.dragOverStatus = null;
                                this.draggedTask = null;
                            }, 80);
                        },

                        async dropTask(newStatus) {
                            if (!this.draggedTask) return;

                            const task = { ...this.draggedTask };
                            this.dragOverStatus = null;

                            if (task.status === newStatus) {
                                return;
                            }

                            await this.updateTaskStatus(task.id, task.status, newStatus);
                        },

                        async changeStatus(taskId, newStatus, select) {
                            const card = document.querySelector(`[data-task-card="${taskId}"]`);
                            const oldStatus = card?.dataset.taskStatus;

                            if (!oldStatus || oldStatus === newStatus) return;

                            select.disabled = true;
                            const success = await this.updateTaskStatus(taskId, oldStatus, newStatus);

                            if (!success) {
                                select.value = oldStatus;
                            }

                            select.disabled = false;
                        },

                        async updateTaskStatus(taskId, oldStatus, newStatus) {
                            const copies = Array.from(document.querySelectorAll(`[data-task-card="${taskId}"]`));
                            copies.forEach(card => card.classList.add('opacity-60'));

                            try {
                                const response = await fetch(statusUrlTemplate.replace('__TASK__', taskId), {
                                    method: 'PATCH',
                                    headers: {
                                        'Accept': 'application/json',
                                        'Content-Type': 'application/json',
                                        'X-CSRF-TOKEN': csrfToken,
                                        'X-Requested-With': 'XMLHttpRequest',
                                    },
                                    body: JSON.stringify({ status: newStatus }),
                                });

                                if (!response.ok) {
                                    throw new Error('status-update-failed');
                                }

                                this.counts[oldStatus] = Math.max(0, this.count(oldStatus) - 1);
                                this.counts[newStatus] = this.count(newStatus) + 1;
                                this.syncTaskCopies(taskId, newStatus);
                                this.showNotice(`وضعیت به «${this.statusLabel(newStatus)}» تغییر کرد.`);

                                return true;
                            } catch (error) {
                                this.showNotice('تغییر وضعیت انجام نشد. دوباره تلاش کنید.');
                                return false;
                            } finally {
                                document
                                    .querySelectorAll(`[data-task-card="${taskId}"]`)
                                    .forEach(card => card.classList.remove('opacity-60'));
                            }
                        },

                        syncTaskCopies(taskId, newStatus) {
                            const copies = Array.from(document.querySelectorAll(`[data-task-card="${taskId}"]`));

                            if (!this.workflowStatuses.includes(newStatus)) {
                                copies.forEach(card => card.remove());
                                return;
                            }

                            copies.forEach(card => {
                                const mode = card.dataset.boardMode;
                                const target = document.querySelector(
                                    `[data-task-cards="${newStatus}"][data-board-mode="${mode}"]`
                                );

                                if (!target) return;

                                card.dataset.taskStatus = newStatus;

                                const select = card.querySelector('[data-task-status-select]');
                                if (select) {
                                    select.value = newStatus;
                                }

                                target.appendChild(card);
                            });
                        },

                        async submitQuickCreate(event) {
                            if (this.creating) return;

                            this.creating = true;
                            this.quickErrors = {};

                            const form = event.currentTarget;
                            const data = new FormData(form);

                            try {
                                const response = await fetch(storeUrl, {
                                    method: 'POST',
                                    headers: {
                                        'Accept': 'application/json',
                                        'X-Requested-With': 'XMLHttpRequest',
                                        'X-CSRF-TOKEN': csrfToken,
                                    },
                                    body: data,
                                });

                                if (response.status === 422) {
                                    const payload = await response.json();
                                    this.quickErrors = payload.errors ?? {};
                                    return;
                                }

                                if (!response.ok) {
                                    throw new Error('task-create-failed');
                                }

                                const payload = await response.json();

                                if (this.filteredBoard) {
                                    window.location.reload();
                                    return;
                                }

                                if (payload.visible_on_current_board) {
                                    this.insertTaskCard(payload.desktop_html, 'desktop', payload.status);
                                    this.insertTaskCard(payload.mobile_html, 'mobile', payload.status);
                                    this.counts[payload.status] = this.count(payload.status) + 1;
                                    this.mobileStatus = payload.status;
                                }

                                form.reset();
                                window.dispatchEvent(new CustomEvent('date-picker-clear', {
                                    detail: { name: 'due_date' },
                                }));

                                this.quickOpen = false;
                                this.showNotice(
                                    payload.visible_on_current_board
                                        ? 'تسک ایجاد شد.'
                                        : 'تسک ایجاد شد و به مسئول انتخاب‌شده ارجاع داده شد.'
                                );
                            } catch (error) {
                                this.showNotice('ثبت تسک انجام نشد. دوباره تلاش کنید.');
                            } finally {
                                this.creating = false;
                            }
                        },

                        insertTaskCard(html, mode, status) {
                            if (!html || !this.workflowStatuses.includes(status)) return;

                            const target = document.querySelector(
                                `[data-task-cards="${status}"][data-board-mode="${mode}"]`
                            );

                            if (!target) return;

                            const template = document.createElement('template');
                            template.innerHTML = html.trim();
                            const node = template.content.firstElementChild;

                            if (!node) return;

                            target.appendChild(node);

                            if (window.Alpine?.initTree) {
                                window.Alpine.initTree(node);
                            }
                        },
                    };
                };
            </script>
        </div>
    @else
        <x-page-header
            :title="$scope === 'all' ? 'همه تسک‌ها' : 'تسک‌های من'"
            description="نمایش کامل تسک‌ها، وضعیت‌های نهایی و فیلترهای دقیق."
        >
            <x-slot:actions>
                @can('tasks.create')
                    <a href="{{ route('tasks.create') }}" class="btn btn-primary">ایجاد تسک</a>
                @endcan
            </x-slot:actions>
        </x-page-header>

        <div class="mb-4 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
            @can('tasks.view_all')
                <div class="flex gap-1 border-b border-gray-200">
                    <a
                        href="{{ route('tasks.index', array_merge(request()->except(['scope', 'page']), ['scope' => 'mine'])) }}"
                        class="border-b-2 px-3 py-2 text-sm font-semibold {{ $scope === 'mine' ? 'border-emerald-500 text-emerald-700' : 'border-transparent text-gray-500' }}"
                    >تسک‌های من</a>
                    <a
                        href="{{ route('tasks.index', array_merge(request()->except(['scope', 'page']), ['scope' => 'all'])) }}"
                        class="border-b-2 px-3 py-2 text-sm font-semibold {{ $scope === 'all' ? 'border-emerald-500 text-emerald-700' : 'border-transparent text-gray-500' }}"
                    >همه تسک‌ها</a>
                </div>
            @endcan

            <div class="flex w-fit rounded-lg border border-gray-200 bg-white p-1">
                <a
                    href="{{ route('tasks.index', array_merge(request()->except(['view', 'page', 'status']), ['view' => 'board'])) }}"
                    class="rounded-md px-4 py-2 text-sm font-semibold text-gray-500 hover:text-gray-800"
                >برد</a>
                <a
                    href="{{ route('tasks.index', array_merge(request()->except(['view', 'page']), ['view' => 'list'])) }}"
                    class="rounded-md bg-emerald-50 px-4 py-2 text-sm font-semibold text-emerald-800"
                >لیست</a>
            </div>
        </div>

        <div class="mb-4 flex gap-2 overflow-x-auto pb-1">
            @foreach(['all' => 'همه', 'today' => 'امروز', 'overdue' => 'عقب‌افتاده', 'in_progress' => 'در حال انجام', 'completed' => 'تکمیل‌شده'] as $value => $label)
                <a
                    href="{{ route('tasks.index', array_merge(request()->except(['quick', 'page']), $value === 'all' ? [] : ['quick' => $value])) }}"
                    class="btn shrink-0 {{ $quick === $value ? 'btn-primary' : 'btn-secondary' }}"
                >{{ $label }}</a>
            @endforeach
        </div>

        <form method="GET" class="panel mb-4 p-3">
            <input type="hidden" name="view" value="list">
            @if($scope === 'all')<input type="hidden" name="scope" value="all">@endif
            @if($quick !== 'all')<input type="hidden" name="quick" value="{{ $quick }}">@endif

            <div class="grid gap-2 md:grid-cols-[minmax(0,1fr)_repeat(4,minmax(150px,auto))]">
                <input class="form-control" name="q" value="{{ $search }}" placeholder="جستجو در عنوان، توضیحات، مشتری یا مسئول">

                @can('tasks.view_all')
                    <select name="assignee_id" class="form-control">
                        <option value="">همه مسئول‌ها</option>
                        @foreach($assignees as $assignee)
                            <option value="{{ $assignee->id }}" @selected($assigneeId === $assignee->id)>{{ $assignee->name }}</option>
                        @endforeach
                    </select>
                @endcan

                <select name="customer_id" class="form-control">
                    <option value="">همه مشتری‌ها</option>
                    @foreach($customers as $customer)
                        <option value="{{ $customer->id }}" @selected($customerId === $customer->id)>{{ $customer->name }}</option>
                    @endforeach
                </select>

                <select name="priority" class="form-control">
                    <option value="">همه اولویت‌ها</option>
                    @foreach($priorities as $item)
                        <option value="{{ $item->value }}" @selected($priority === $item->value)>{{ $item->label() }}</option>
                    @endforeach
                </select>

                <select name="status" class="form-control">
                    <option value="">همه وضعیت‌ها</option>
                    @foreach($statuses as $item)
                        <option value="{{ $item->value }}" @selected($status === $item->value)>{{ $item->label() }}</option>
                    @endforeach
                </select>

                <div class="flex gap-2">
                    <button type="submit" class="btn btn-primary flex-1">اعمال</button>
                    <a
                        href="{{ route('tasks.index', array_filter(['view' => 'list', 'scope' => $scope === 'all' ? 'all' : null])) }}"
                        class="btn btn-secondary"
                    >پاک</a>
                </div>
            </div>
        </form>

        <div class="panel overflow-hidden">
            @if($tasks->isEmpty())
                <x-empty-state message="فعلاً تسکی برای شما وجود ندارد.">
                    <x-slot:action>
                        @can('tasks.create')
                            <a class="btn btn-primary" href="{{ route('tasks.create') }}">ایجاد تسک جدید</a>
                        @endcan
                    </x-slot:action>
                </x-empty-state>
            @else
                <div class="ui-table-wrap hidden md:block">
                    <table class="ui-table min-w-[950px]">
                        <thead class="bg-gray-50 text-xs text-gray-500">
                            <tr>
                                <th class="p-4">عنوان</th>
                                <th class="p-4">مشتری</th>
                                <th class="p-4">مسئول</th>
                                <th class="p-4">اولویت</th>
                                <th class="p-4">وضعیت</th>
                                <th class="p-4">شروع</th>
                                <th class="p-4">ددلاین</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($tasks as $task)
                                <tr>
                                    <td class="p-4"><a class="font-semibold hover:text-emerald-700" href="{{ route('tasks.show', $task) }}">{{ $task->title }}</a></td>
                                    <td class="p-4 text-gray-600">{{ $task->customer?->name ?: '—' }}</td>
                                    <td class="p-4">{{ $task->assignee->name }}</td>
                                    <td class="p-4"><x-badge :type="$task->priority->intent()">{{ $task->priority->label() }}</x-badge></td>
                                    <td class="p-4"><x-badge :type="$task->status->intent()">{{ $task->status->label() }}</x-badge></td>
                                    <td class="p-4">{{ app(\App\Support\DatePresenter::class)->date($task->start_date) }}</td>
                                    <td class="p-4">
                                        <span class="{{ $task->isOverdue() ? 'font-semibold text-red-700' : '' }}">
                                            {{ app(\App\Support\DatePresenter::class)->date($task->due_date) }}
                                        </span>
                                        @if($task->isOverdue()) <x-badge type="danger" class="mr-1">عقب‌افتاده</x-badge>@endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="divide-y divide-gray-100 md:hidden">
                    @foreach($tasks as $task)
                        <a href="{{ route('tasks.show', $task) }}" class="block min-h-32 p-4 active:bg-gray-50">
                            <div class="flex items-start justify-between gap-3">
                                <strong class="leading-6">{{ $task->title }}</strong>
                                @if($task->isOverdue())<x-badge type="danger">عقب‌افتاده</x-badge>@endif
                            </div>
                            @if($task->customer)<p class="mt-1 text-sm text-gray-500">{{ $task->customer->name }}</p>@endif
                            <div class="mt-3 flex flex-wrap gap-2">
                                <x-badge :type="$task->priority->intent()">{{ $task->priority->label() }}</x-badge>
                                <x-badge :type="$task->status->intent()">{{ $task->status->label() }}</x-badge>
                            </div>
                            <div class="mt-3 flex items-center justify-between text-sm">
                                <span>ددلاین: {{ app(\App\Support\DatePresenter::class)->date($task->due_date) }}</span>
                                @can('tasks.view_all')<span class="text-gray-500">{{ $task->assignee->name }}</span>@endcan
                            </div>
                        </a>
                    @endforeach
                </div>

                <div class="border-t border-gray-100 p-4">{{ $tasks->links() }}</div>
            @endif
        </div>
    @endif
</x-layouts.app>
