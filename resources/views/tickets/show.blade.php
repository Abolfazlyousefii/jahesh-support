<x-layouts.app :title="$ticket->subject">
    <div x-data="{ convertOpen: false }">
        <x-page-header :title="$ticket->subject" :description="'#'.$ticket->id.' — '.$ticket->customer->name">
            <x-slot:actions>
                <a href="{{ route('tickets.index') }}" class="btn btn-secondary">بازگشت</a>
                @if($ticket->task)
                    @if($ticket->task->trashed())
                        <x-badge type="neutral">تسک مرتبط حذف شده</x-badge>
                    @else
                        <a href="{{ route('tasks.show', $ticket->task) }}" class="btn btn-secondary">مشاهده تسک مرتبط</a>
                    @endif
                @else
                    @can('convertToTask', $ticket)
                        <button type="button" class="btn btn-primary" @click="convertOpen = true">تبدیل به تسک</button>
                    @endcan
                @endif
            </x-slot:actions>
        </x-page-header>

        <div class="mb-4 flex flex-wrap items-center gap-2">
            <x-badge :type="$ticket->status->intent()">{{ $ticket->status->label() }}</x-badge>
            <x-badge :type="$ticket->priority->intent()">اولویت {{ $ticket->priority->label() }}</x-badge>
            @if($ticket->hasUnreadCustomerReply())<x-badge type="success">پیام جدید مشتری</x-badge>@endif
        </div>

        <div class="grid gap-4 xl:grid-cols-[minmax(0,1fr)_320px]">
            <section class="panel overflow-hidden">
                <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4">
                    <div><h2 class="font-bold">گفتگو با مشتری</h2><p class="mt-1 text-xs text-gray-500">پیام‌های عمومی برای مشتری قابل مشاهده‌اند؛ یادداشت داخلی فقط برای تیم است.</p></div>
                    <span class="text-xs text-gray-400">{{ $ticket->messages->count() }} پیام</span>
                </div>

                <div class="max-h-[62vh] space-y-4 overflow-y-auto bg-gray-50/40 p-4 sm:p-5" id="ticket-chat">
                    @foreach($ticket->messages as $message)
                        @php
                            $internal = $message->message_type === \App\Enums\TicketMessageType::Internal;
                            $fromCustomer = $message->author instanceof \App\Models\Customer;
                        @endphp

                        @if($internal)
                            <article class="mx-auto max-w-[92%] rounded-xl border border-amber-200 bg-amber-50 p-4">
                                <div class="mb-2 flex flex-wrap items-center justify-between gap-2 text-xs text-amber-800">
                                    <strong>یادداشت داخلی — {{ $message->author?->name }}</strong>
                                    <time>{{ app(\App\Support\DatePresenter::class)->dateTime($message->created_at) }}</time>
                                </div>
                                <p class="whitespace-pre-line leading-7 text-gray-800">{{ $message->body }}</p>
                            </article>
                        @else
                            <article class="max-w-[92%] rounded-xl border p-4 sm:max-w-[78%] {{ $fromCustomer ? 'mr-auto border-emerald-100 bg-emerald-50' : 'ml-auto border-gray-200 bg-white' }}">
                                <div class="mb-2 flex flex-wrap items-center justify-between gap-3 text-xs text-gray-500">
                                    <strong class="text-gray-700">{{ $fromCustomer ? $ticket->customer->name : ($message->author?->name ?: 'پشتیبانی') }}</strong>
                                    <time>{{ app(\App\Support\DatePresenter::class)->dateTime($message->created_at) }}</time>
                                </div>
                                <p class="whitespace-pre-line leading-7 text-gray-800">{{ $message->body }}</p>
                            </article>
                        @endif
                    @endforeach
                </div>

                @if(!$ticket->status->isClosed())
                    <div class="border-t border-gray-100 bg-white p-4" x-data="{ mode: 'public' }">
                        <div class="mb-3 flex flex-wrap gap-2">
                            @can('reply', $ticket)<button type="button" class="btn" :class="mode === 'public' ? 'btn-primary' : 'btn-secondary'" @click="mode='public'">پاسخ به مشتری</button>@endcan
                            @can('internalNote', $ticket)<button type="button" class="btn" :class="mode === 'internal' ? 'btn-primary' : 'btn-secondary'" @click="mode='internal'">یادداشت داخلی</button>@endcan
                        </div>

                        @can('reply', $ticket)
                            <form x-show="mode === 'public'" method="POST" action="{{ route('tickets.reply', $ticket) }}">
                                @csrf
                                <label class="form-label" for="public-body">متن پاسخ</label>
                                <textarea id="public-body" name="body" rows="4" class="form-control resize-none" placeholder="پاسخ خود را برای مشتری بنویسید..." required>{{ old('body') }}</textarea>
                                @error('body')<p class="form-error">{{ $message }}</p>@enderror
                                @error('after_reply_status')<p class="form-error">{{ $message }}</p>@enderror

                                <div class="mt-3 flex flex-wrap items-center justify-between gap-2">
                                    <p class="text-xs text-gray-500">می‌توانید هنگام ارسال، وضعیت را هم بدون مرحله اضافه تغییر دهید.</p>
                                    <div class="flex flex-wrap gap-2">
                                        <x-button>فقط ارسال</x-button>
                                        @can('updateStatus', $ticket)
                                            <button type="submit" name="after_reply_status" value="waiting_customer" class="btn btn-secondary">ارسال و انتظار مشتری</button>
                                            <button type="submit" name="after_reply_status" value="in_progress" class="btn btn-secondary">ارسال و در حال انجام</button>
                                            <button type="submit" name="after_reply_status" value="resolved" class="btn btn-secondary">ارسال و حل شد</button>
                                        @endcan
                                    </div>
                                </div>
                            </form>
                        @endcan

                        @can('internalNote', $ticket)
                            <form x-cloak x-show="mode === 'internal'" method="POST" action="{{ route('tickets.internal-note', $ticket) }}" class="rounded-xl border border-amber-200 bg-amber-50 p-4">
                                @csrf
                                <p class="mb-2 text-sm font-bold text-amber-800">یادداشت داخلی — مشتری این پیام را نمی‌بیند</p>
                                <textarea name="body" rows="4" class="form-control resize-none" placeholder="یادداشت برای اعضای تیم..." required></textarea>
                                @error('body')<p class="form-error">{{ $message }}</p>@enderror
                                <div class="mt-3 flex justify-end"><x-button>ثبت یادداشت</x-button></div>
                            </form>
                        @endcan
                    </div>
                @else
                    <div class="border-t border-gray-100 bg-gray-50 p-5 text-center text-sm text-gray-500">این تیکت بسته شده و فقط خواندنی است.</div>
                @endif
            </section>

            <aside class="space-y-4">
                <section class="panel p-5">
                    <div class="mb-4 flex items-center justify-between"><h2 class="font-bold">جزئیات تیکت</h2><span class="text-xs text-gray-400">#{{ $ticket->id }}</span></div>
                    <dl class="space-y-4">
                        <div><dt class="text-xs text-gray-500">مشتری</dt><dd class="mt-1 font-semibold">{{ $ticket->customer->name }}</dd></div>
                        <div><dt class="text-xs text-gray-500">شماره اصلی</dt><dd class="mt-1" dir="ltr">{{ $ticket->customer->primaryPhone?->phone ?: '—' }}</dd></div>
                        <div><dt class="text-xs text-gray-500">مسئول فعلی</dt><dd class="mt-1 font-semibold">{{ $ticket->assignee?->name ?: 'تخصیص‌نیافته' }}</dd></div>
                        <div><dt class="text-xs text-gray-500">تاریخ ثبت</dt><dd class="mt-1">{{ app(\App\Support\DatePresenter::class)->dateTime($ticket->created_at) }}</dd></div>
                        <div><dt class="text-xs text-gray-500">آخرین فعالیت</dt><dd class="mt-1">{{ app(\App\Support\DatePresenter::class)->dateTime($ticket->updated_at) }}</dd></div>
                    </dl>
                </section>

                @if($ticket->task)
                    <section class="panel p-5">
                        <div class="mb-3 flex items-center justify-between"><h2 class="font-bold">تسک مرتبط</h2><span class="text-xs text-gray-400">#{{ $ticket->task->id }}</span></div>
                        @if($ticket->task->trashed())
                            <p class="text-sm text-gray-500">تسک مرتبط حذف شده اما سابقه ارتباط حفظ شده است.</p>
                        @else
                            <strong class="block">{{ $ticket->task->title }}</strong>
                            <div class="mt-3 flex flex-wrap gap-2"><x-badge :type="$ticket->task->status->intent()">{{ $ticket->task->status->label() }}</x-badge><span class="text-xs text-gray-500">{{ $ticket->task->assignee?->name }}</span></div>
                            <a href="{{ route('tasks.show', $ticket->task) }}" class="btn btn-secondary mt-4 w-full">باز کردن تسک</a>
                        @endif
                    </section>
                @endif

                @can('assign', $ticket)
                    <form method="POST" action="{{ route('tickets.assignment.update', $ticket) }}" class="panel p-4">
                        @csrf @method('PATCH')
                        <label for="assignee_id" class="form-label">ارجاع به مسئول</label>
                        <select id="assignee_id" name="assignee_id" class="form-control" required>
                            <option value="" disabled @selected(!$ticket->assigned_to)>انتخاب مسئول</option>
                            @foreach($assignees as $assignee)<option value="{{ $assignee->id }}" @selected($ticket->assigned_to === $assignee->id)>{{ $assignee->name }}</option>@endforeach
                        </select>
                        <p class="mt-2 text-xs text-gray-500">اگر تیکت جدید باشد، بعد از ارجاع خودکار به «در حال بررسی» می‌رود.</p>
                        <x-button class="mt-3 w-full">ثبت مسئول</x-button>
                    </form>
                @endcan

                @can('updateStatus', $ticket)
                    <form method="POST" action="{{ route('tickets.status.update', $ticket) }}" class="panel p-4">
                        @csrf @method('PATCH')
                        <label for="ticket-status" class="form-label">وضعیت تیکت</label>
                        <select id="ticket-status" name="status" class="form-control">
                            @foreach($statuses as $status)<option value="{{ $status->value }}" @selected($ticket->status === $status)>{{ $status->label() }}</option>@endforeach
                        </select>
                        <x-button class="mt-3 w-full">ثبت وضعیت</x-button>
                    </form>
                @endcan
            </aside>
        </div>

        @can('convertToTask', $ticket)
            @if(!$ticket->task)
                <div x-cloak x-show="convertOpen" class="fixed inset-0 z-50 flex items-end justify-center bg-black/30 p-0 sm:items-center sm:p-4" @keydown.escape.window="convertOpen = false">
                    <div class="w-full max-w-2xl rounded-t-lg bg-white p-5 sm:rounded-lg sm:p-6" @click.outside="convertOpen = false">
                        <div class="mb-5 flex items-start justify-between gap-4">
                            <div><h2 class="text-lg font-bold">تبدیل تیکت به تسک</h2><p class="mt-1 text-sm text-gray-500">تسک به این تیکت متصل می‌ماند و وضعیت تیکت وارد «در حال انجام» می‌شود.</p></div>
                            <button type="button" class="btn btn-secondary" @click="convertOpen = false">بستن</button>
                        </div>
                        <form method="POST" action="{{ route('tickets.convert.store', $ticket) }}">
                            @csrf
                            <div class="grid gap-4 sm:grid-cols-2">
                                <div class="sm:col-span-2"><x-input label="عنوان تسک" name="title" :value="$ticket->subject" required /></div>
                                <div>
                                    <label for="convert-assignee" class="form-label">مسئول تسک</label>
                                    <select id="convert-assignee" name="assignee_id" class="form-control" required>
                                        @foreach($assignees as $assignee)<option value="{{ $assignee->id }}" @selected(old('assignee_id', $ticket->assigned_to ?: auth()->id()) === $assignee->id)>{{ $assignee->name }}</option>@endforeach
                                    </select>
                                </div>
                                <div class="flex items-end"><div class="w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-3 text-sm">اولویت تسک: <strong>{{ $ticket->priority->label() }}</strong></div></div>
                                <x-persian-date-input label="تاریخ شروع" name="start_date" />
                                <x-persian-date-input label="ددلاین" name="due_date" />
                            </div>
                            @error('ticket')<p class="form-error mt-3">{{ $message }}</p>@enderror
                            <div class="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end"><button type="button" class="btn btn-secondary" @click="convertOpen = false">انصراف</button><x-button>ساخت تسک و شروع اجرا</x-button></div>
                        </form>
                    </div>
                </div>
            @endif
        @endcan

        @can('delete', $ticket)
            <div class="mt-6 border-t border-gray-200 pt-5"><form method="POST" action="{{ route('tickets.destroy', $ticket) }}" data-confirm="این تیکت حذف شود؟">@csrf @method('DELETE')<x-button variant="danger">حذف تیکت</x-button></form></div>
        @endcan
    </div>

    <script>document.addEventListener('DOMContentLoaded', () => { const chat = document.getElementById('ticket-chat'); if (chat) chat.scrollTop = chat.scrollHeight; });</script>
</x-layouts.app>
