<x-layouts.app title="تبدیل تیکت به تسک">
    <x-page-header title="تبدیل به تسک" :description="'تیکت #'.$ticket->id" />
    <form method="POST" action="{{ route('tickets.convert.store', $ticket) }}" class="panel p-5 sm:p-6">@csrf
        <div class="grid gap-4 sm:grid-cols-2">
            <div class="sm:col-span-2"><x-input label="عنوان تسک" name="title" :value="$ticket->subject" required autofocus /></div>
            <div><label for="assignee_id" class="form-label">مسئول</label><select id="assignee_id" name="assignee_id" class="form-control" required>@foreach($assignees as $assignee)<option value="{{ $assignee->id }}" @selected(old('assignee_id', $ticket->assigned_to ?: auth()->id()) === $assignee->id)>{{ $assignee->name }}</option>@endforeach</select>@error('assignee_id')<p class="form-error">{{ $message }}</p>@enderror</div>
            <div class="flex items-end"><div class="w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-3 text-sm">اولویت تسک: <strong>{{ $ticket->priority->label() }}</strong></div></div>
            <x-persian-date-input label="تاریخ شروع" name="start_date" />
            <x-persian-date-input label="ددلاین" name="due_date" />
        </div>
        <div class="mt-4 rounded-lg border border-emerald-100 bg-emerald-50 p-3 text-sm text-emerald-800">بعد از ساخت تسک، وضعیت تیکت به‌صورت خودکار «در حال انجام» می‌شود و ارتباط دوطرفه تیکت و تسک حفظ خواهد شد.</div>
        <div class="mt-6 flex flex-col-reverse gap-2 sm:flex-row"><a href="{{ route('tickets.show', $ticket) }}" class="btn btn-secondary">انصراف</a><x-button>ساخت تسک</x-button></div>
    </form>
</x-layouts.app>
