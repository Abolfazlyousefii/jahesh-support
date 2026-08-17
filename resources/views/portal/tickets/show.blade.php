<x-layouts.portal :title="$ticket->subject">
    <div class="mb-5"><a href="{{ route('portal.tickets.index') }}" class="text-sm text-gray-500">بازگشت به درخواست‌ها</a><div class="mt-3 flex flex-wrap items-center gap-2"><h1 class="ml-auto text-xl font-bold">{{ $ticket->subject }}</h1><span class="text-sm text-gray-500">#{{ $ticket->id }}</span><x-badge :type="$ticket->status->intent()">{{ $ticket->status->label() }}</x-badge></div></div>
    <section class="panel overflow-hidden">
        <div class="space-y-4 p-4 sm:p-5">
            @foreach($ticket->messages as $message)
                @php($fromCustomer = $message->author instanceof \App\Models\Customer)
                <article class="max-w-[90%] rounded-lg border p-3 sm:max-w-[75%] {{ $fromCustomer ? 'mr-auto border-emerald-100 bg-emerald-50/50' : 'ml-auto border-gray-200 bg-gray-50' }}"><div class="mb-2 flex items-center justify-between gap-4 text-xs text-gray-500"><span>{{ $fromCustomer ? 'شما' : 'پشتیبانی جهش' }}</span><time>{{ app(\App\Support\DatePresenter::class)->dateTime($message->created_at) }}</time></div><p class="whitespace-pre-line leading-7">{{ $message->body }}</p></article>
            @endforeach
        </div>
        @if($ticket->status->isClosed())
            <div class="border-t border-gray-100 bg-gray-50 p-5 text-center"><p class="text-sm text-gray-600">این درخواست بسته شده است. برای موضوع جدید لطفاً درخواست تازه‌ای ثبت کنید.</p><a href="{{ route('portal.tickets.create') }}" class="btn btn-primary mt-3">ثبت درخواست جدید</a></div>
        @else
            <form method="POST" action="{{ route('portal.tickets.replies.store', $ticket) }}" class="border-t border-gray-100 p-4" x-data="{ resize(event) { event.target.style.height = 'auto'; event.target.style.height = event.target.scrollHeight + 'px' } }">@csrf<label for="reply-body" class="sr-only">پیام خود را بنویسید</label><textarea id="reply-body" name="body" rows="3" class="form-control resize-none" placeholder="پیام خود را بنویسید..." @input="resize" required>{{ old('body') }}</textarea>@error('body')<p class="form-error">{{ $message }}</p>@enderror<div class="mt-3 flex justify-end"><x-button>ارسال</x-button></div></form>
        @endif
    </section>
</x-layouts.portal>
