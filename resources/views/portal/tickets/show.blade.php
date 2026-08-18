<x-layouts.portal :title="$ticket->subject">
    <section class="portal-page-head portal-page-head-compact">
        <div>
            <a href="{{ route('portal.tickets.index') }}" class="portal-back-link">بازگشت به درخواست‌ها</a>
            <div class="portal-ticket-heading">
                <h1>{{ $ticket->subject }}</h1>
                <x-badge :type="$ticket->status->intent()">{{ $ticket->status->label() }}</x-badge>
            </div>
            <p>شماره درخواست #{{ $ticket->id }}</p>
        </div>
    </section>

    <div class="portal-conversation-grid">
        <section class="portal-card portal-chat-card">
            <div class="portal-chat-head">
                <div>
                    <strong>گفتگو با پشتیبانی جهش</strong>
                    <span>پاسخ‌ها در همین گفتگو ثبت و نگهداری می‌شوند.</span>
                </div>
            </div>

            <div class="portal-chat-body" id="portal-ticket-chat">
                @foreach($ticket->messages as $message)
                    @php($fromCustomer = $message->author instanceof \App\Models\Customer)
                    <article class="portal-message {{ $fromCustomer ? 'customer' : 'support' }}">
                        <div class="portal-message-meta">
                            <strong>{{ $fromCustomer ? 'شما' : 'پشتیبانی جهش' }}</strong>
                            <time>{{ app(\App\Support\DatePresenter::class)->dateTime($message->created_at) }}</time>
                        </div>
                        <p>{{ $message->body }}</p>
                    </article>
                @endforeach
            </div>

            @if($ticket->status->isClosed())
                <div class="portal-chat-closed">
                    <strong>این درخواست بسته شده است.</strong>
                    <p>برای موضوع جدید لطفاً درخواست دیگری ثبت کنید.</p>
                    <a href="{{ route('portal.tickets.create') }}" class="btn btn-primary">درخواست جدید</a>
                </div>
            @else
                @if($ticket->status === \App\Enums\TicketStatus::Resolved)
                    <div class="portal-chat-notice">اگر مشکل هنوز باقی است، پاسخ بدهید تا درخواست دوباره وارد مرحله بررسی شود.</div>
                @endif

                <form method="POST" action="{{ route('portal.tickets.replies.store', $ticket) }}" class="portal-reply-form" x-data="{ resize(event) { event.target.style.height = 'auto'; event.target.style.height = event.target.scrollHeight + 'px' } }">
                    @csrf
                    <label for="reply-body" class="form-label">پاسخ شما</label>
                    <textarea id="reply-body" name="body" rows="3" class="form-control resize-none" placeholder="پیام خود را بنویسید..." @input="resize" required>{{ old('body') }}</textarea>
                    @error('body')<p class="form-error">{{ $message }}</p>@enderror
                    <div class="portal-reply-actions"><x-button>ارسال پیام</x-button></div>
                </form>
            @endif
        </section>

        <aside class="portal-card portal-ticket-info">
            <h2>جزئیات درخواست</h2>
            <dl>
                <div><dt>وضعیت</dt><dd><x-badge :type="$ticket->status->intent()">{{ $ticket->status->label() }}</x-badge></dd></div>
                <div><dt>اولویت</dt><dd><x-badge :type="$ticket->priority->intent()">{{ $ticket->priority->label() }}</x-badge></dd></div>
                <div><dt>شماره</dt><dd>#{{ $ticket->id }}</dd></div>
                <div><dt>آخرین بروزرسانی</dt><dd>{{ app(\App\Support\DatePresenter::class)->dateTime($ticket->updated_at) }}</dd></div>
            </dl>
            <div class="portal-soft-note">برای حفظ سابقه درخواست، پاسخ‌های مربوط به همین موضوع را داخل همین گفتگو ادامه دهید.</div>
        </aside>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const chat = document.getElementById('portal-ticket-chat');
            if (chat) chat.scrollTop = chat.scrollHeight;
        });
    </script>
</x-layouts.portal>
