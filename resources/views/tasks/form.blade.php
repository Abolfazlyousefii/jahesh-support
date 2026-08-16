<div class="grid gap-4 sm:grid-cols-2">
    <div class="sm:col-span-2">
        <x-input label="عنوان" name="title" :value="$task?->title" required autofocus />
    </div>

    <div class="sm:col-span-2">
        <label for="description" class="form-label">توضیحات</label>
        <textarea id="description" name="description" rows="5" class="form-control">{{ old('description', $task?->description) }}</textarea>
        @error('description')<p class="form-error">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="customer_id" class="form-label">مشتری <span class="font-normal text-gray-400">(اختیاری)</span></label>
        <select id="customer_id" name="customer_id" class="form-control">
            <option value="">بدون مشتری</option>
            @foreach($customers as $customer)
                <option value="{{ $customer->id }}" @selected((string) old('customer_id', $task?->customer_id) === (string) $customer->id)>{{ $customer->name }}{{ $customer->company_name ? ' — '.$customer->company_name : '' }}</option>
            @endforeach
        </select>
        @error('customer_id')<p class="form-error">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="assignee_id" class="form-label">مسئول</label>
        @if($canAssign)
            <select id="assignee_id" name="assignee_id" class="form-control" required>
                @foreach($assignees as $assignee)
                    <option value="{{ $assignee->id }}" @selected((string) old('assignee_id', $task?->assignee_id ?? auth()->id()) === (string) $assignee->id)>{{ $assignee->name }}{{ $assignee->roles->isNotEmpty() ? ' — '.$assignee->roles->pluck('title')->join('، ') : '' }}</option>
                @endforeach
            </select>
        @else
            <div class="form-control flex items-center bg-gray-50 text-gray-600">{{ auth()->user()->name }}</div>
            <input type="hidden" name="assignee_id" value="{{ auth()->id() }}">
        @endif
        @error('assignee_id')<p class="form-error">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="priority" class="form-label">اولویت</label>
        <select id="priority" name="priority" class="form-control" required>
            @foreach($priorities as $priority)
                <option value="{{ $priority->value }}" @selected(old('priority', $task?->priority?->value ?? \App\Enums\TaskPriority::Normal->value) === $priority->value)>{{ $priority->label() }}</option>
            @endforeach
        </select>
        @error('priority')<p class="form-error">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="status" class="form-label">وضعیت</label>
        <select id="status" name="status" class="form-control" required>
            @foreach($statuses as $status)
                <option value="{{ $status->value }}" @selected(old('status', $task?->status?->value ?? \App\Enums\TaskStatus::New->value) === $status->value)>{{ $status->label() }}</option>
            @endforeach
        </select>
        @error('status')<p class="form-error">{{ $message }}</p>@enderror
    </div>

    <x-input label="تاریخ شروع" name="start_date" type="date" :value="$task?->start_date?->format('Y-m-d')" />
    <x-input label="ددلاین" name="due_date" type="date" :value="$task?->due_date?->format('Y-m-d')" />
</div>

<p class="mt-3 text-xs text-gray-500">تاریخ ورودی میلادی است و پس از ذخیره در پنل به‌صورت شمسی نمایش داده می‌شود.</p>

<div class="mt-6 flex flex-col-reverse gap-2 sm:flex-row">
    <a href="{{ $task ? route('tasks.show', $task) : route('tasks.index') }}" class="btn btn-secondary">انصراف</a>
    <x-button>ذخیره</x-button>
</div>
