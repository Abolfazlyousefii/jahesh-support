<x-layouts.portal title="درخواست جدید">
    <x-page-header title="درخواست پشتیبانی جدید" description="موضوع و توضیحات درخواست خود را بنویسید." />
    <form method="POST" action="{{ route('portal.tickets.store') }}" class="panel space-y-4 p-5 sm:p-6">@csrf
        <x-input label="موضوع درخواست" name="subject" required autofocus />
        <div><label for="priority" class="form-label">اولویت</label><select id="priority" name="priority" class="form-control">@foreach($priorities as $priority)<option value="{{ $priority->value }}" @selected(old('priority', 'normal') === $priority->value)>{{ $priority->label() }}</option>@endforeach</select>@error('priority')<p class="form-error">{{ $message }}</p>@enderror</div>
        <div><label for="description" class="form-label">توضیحات</label><textarea id="description" name="description" rows="7" class="form-control" required>{{ old('description') }}</textarea>@error('description')<p class="form-error">{{ $message }}</p>@enderror</div>
        <div class="flex flex-col-reverse gap-2 sm:flex-row"><a href="{{ route('portal.tickets.index') }}" class="btn btn-secondary">انصراف</a><x-button>ارسال درخواست</x-button></div>
    </form>
</x-layouts.portal>
