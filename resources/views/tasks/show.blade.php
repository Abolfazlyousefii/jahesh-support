<x-layouts.app :title="$task->title">
    <x-page-header :title="$task->title" :description="$task->customer?->name">
        <x-slot:actions>@can('update', $task)<a href="{{ route('tasks.edit', $task) }}" class="btn btn-primary">ویرایش</a>@endcan</x-slot:actions>
    </x-page-header>

    <div class="mb-4 flex flex-wrap gap-2">
        <x-badge :type="$task->status->intent()">{{ $task->status->label() }}</x-badge>
        <x-badge :type="$task->priority->intent()">اولویت {{ $task->priority->label() }}</x-badge>
        @if($task->isOverdue())<x-badge type="danger">عقب‌افتاده</x-badge>@endif
        @if($task->sourceTicket)@if($task->sourceTicket->trashed())<span class="text-sm text-gray-500">منبع: تیکت حذف‌شده #{{ $task->sourceTicket->id }}</span>@else<a href="{{ route('tickets.show', $task->sourceTicket) }}" class="text-sm font-semibold text-emerald-700">منبع: تیکت #{{ $task->sourceTicket->id }}</a>@endif @endif
    </div>

    <div class="grid gap-4 lg:grid-cols-3">
        <section class="panel p-5 lg:col-span-2">
            <h2 class="mb-3 text-base font-bold">توضیحات</h2>
            <p class="whitespace-pre-line leading-7 text-gray-700">{{ $task->description ?: 'توضیحی ثبت نشده است.' }}</p>
        </section>

        @can('updateStatus', $task)
            <section class="panel p-5">
                <h2 class="mb-3 text-base font-bold">تغییر سریع وضعیت</h2>
                <form method="POST" action="{{ route('tasks.status.update', $task) }}" class="space-y-3">
                    @csrf @method('PATCH')
                    <label for="quick-status" class="sr-only">وضعیت</label>
                    <select id="quick-status" name="status" class="form-control">@foreach($statuses as $status)<option value="{{ $status->value }}" @selected($task->status === $status)>{{ $status->label() }}</option>@endforeach</select>
                    <x-button class="w-full">ثبت وضعیت</x-button>
                </form>
            </section>
        @endcan

        <section class="panel p-5 lg:col-span-3">
            <h2 class="mb-4 text-base font-bold">جزئیات تسک</h2>
            <dl class="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                <div><dt class="text-xs text-gray-500">مشتری</dt><dd class="mt-1">{{ $task->customer?->name ?: '—' }}</dd></div>
                <div><dt class="text-xs text-gray-500">مسئول</dt><dd class="mt-1">{{ $task->assignee->name }}</dd></div>
                <div><dt class="text-xs text-gray-500">ایجادکننده</dt><dd class="mt-1">{{ $task->creator->name }}</dd></div>
                <div><dt class="text-xs text-gray-500">تاریخ ثبت</dt><dd class="mt-1">{{ app(\App\Support\DatePresenter::class)->date($task->created_at) }}</dd></div>
                <div><dt class="text-xs text-gray-500">تاریخ شروع</dt><dd class="mt-1">{{ app(\App\Support\DatePresenter::class)->date($task->start_date) }}</dd></div>
                <div><dt class="text-xs text-gray-500">ددلاین</dt><dd class="mt-1">{{ app(\App\Support\DatePresenter::class)->date($task->due_date) }}</dd></div>
                @if($task->completed_at)<div><dt class="text-xs text-gray-500">تاریخ تکمیل</dt><dd class="mt-1">{{ app(\App\Support\DatePresenter::class)->dateTime($task->completed_at) }}</dd></div>@endif
            </dl>
        </section>
    </div>

    @can('delete', $task)
        <div class="mt-6 border-t border-gray-200 pt-5"><form method="POST" action="{{ route('tasks.destroy', $task) }}" data-confirm="این تسک حذف شود؟">@csrf @method('DELETE')<x-button variant="danger">حذف تسک</x-button></form></div>
    @endcan
</x-layouts.app>
