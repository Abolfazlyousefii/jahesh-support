@php
    $mode = $mode ?? 'desktop';
    $isMobile = $mode === 'mobile';
@endphp

<article
    class="group rounded-xl border border-gray-200 bg-white p-3 shadow-sm transition duration-150 hover:border-gray-300 hover:shadow"
    data-task-card="{{ $task->id }}"
    data-task-status="{{ $task->status->value }}"
    data-board-mode="{{ $mode }}"
    @if(! $isMobile)
        @can('updateStatus', $task)
            draggable="true"
            @dragstart="startDrag($event, {{ $task->id }}, '{{ $task->status->value }}')"
            @dragend="endDrag($event)"
            @mouseup="dragArmed = null"
        @endcan
    @endif
>
    <div class="flex items-start justify-between gap-2">
        <a
            href="{{ route('tasks.show', $task) }}"
            class="min-w-0 flex-1 font-bold leading-6 text-gray-900 hover:text-emerald-700"
        >{{ $task->title }}</a>

        @if(! $isMobile)
            @can('updateStatus', $task)
                <button
                    type="button"
                    class="grid h-8 w-8 shrink-0 cursor-grab place-items-center rounded-lg text-gray-400 hover:bg-gray-100 hover:text-gray-700 active:cursor-grabbing"
                    @mousedown="dragArmed = {{ $task->id }}"
                    @mouseup="dragArmed = null"
                    title="برای تغییر وضعیت بکشید"
                    aria-label="جابه‌جایی تسک"
                >
                    <span class="text-base leading-none">⋮⋮</span>
                </button>
            @endcan
        @endif
    </div>

    @if($task->customer)
        <p class="mt-1 truncate text-xs text-gray-500">{{ $task->customer->name }}</p>
    @endif

    <div class="mt-3 flex flex-wrap items-center gap-1.5">
        <x-badge :type="$task->priority->intent()">{{ $task->priority->label() }}</x-badge>

        @if($task->isOverdue())
            <x-badge type="danger">عقب‌افتاده</x-badge>
        @elseif($task->due_date?->isToday())
            <x-badge type="warning">امروز</x-badge>
        @endif
    </div>

    @if($task->due_date || $scope === 'all')
        <div class="mt-3 space-y-1.5 border-t border-gray-100 pt-2.5 text-xs text-gray-500">
            @if($task->due_date)
                <div class="flex items-center justify-between gap-2">
                    <span>ددلاین</span>
                    <span class="{{ $task->isOverdue() ? 'font-bold text-red-700' : 'font-semibold text-gray-700' }}">
                        {{ app(\App\Support\DatePresenter::class)->date($task->due_date) }}
                    </span>
                </div>
            @endif

            @if($scope === 'all')
                <div class="flex items-center justify-between gap-2">
                    <span>مسئول</span>
                    <span class="truncate font-semibold text-gray-700">{{ $task->assignee->name }}</span>
                </div>
            @endif
        </div>
    @endif

    @if($isMobile)
        @can('updateStatus', $task)
            <div class="mt-3">
                <label class="sr-only" for="task-status-{{ $mode }}-{{ $task->id }}">تغییر وضعیت</label>
                <select
                    id="task-status-{{ $mode }}-{{ $task->id }}"
                    class="form-control !min-h-10 !py-1.5 text-xs"
                    data-task-status-select
                    @change="changeStatus({{ $task->id }}, $event.target.value, $event.target)"
                >
                    @foreach($statuses as $selectStatus)
                        <option value="{{ $selectStatus->value }}" @selected($task->status === $selectStatus)>{{ $selectStatus->label() }}</option>
                    @endforeach
                </select>
            </div>
        @endcan
    @endif
</article>
