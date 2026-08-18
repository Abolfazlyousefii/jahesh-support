<x-layouts.portal title="تیکت‌ها">
    <section class="portal-page-head">
        <div>
            <span class="portal-eyebrow">پشتیبانی</span>
            <h1>تیکت‌های من</h1>
            <p>آرشیو کامل درخواست‌های شما؛ از تیکت‌های باز تا موارد حل‌شده و بسته‌شده.</p>
        </div>
        <a href="{{ route('portal.tickets.create') }}" class="btn btn-primary portal-cta">درخواست جدید</a>
    </section>

    <section class="portal-ticket-summary">
        <div class="portal-ticket-summary-item primary">
            <span>همه تیکت‌ها</span>
            <strong>{{ number_format($tickets->total()) }}</strong>
        </div>
        <div class="portal-ticket-summary-item">
            <span>در جریان</span>
            <strong>{{ number_format($activeTicketCount) }}</strong>
        </div>
        <div class="portal-ticket-summary-item">
            <span>حل یا بسته شده</span>
            <strong>{{ number_format($finishedTicketCount) }}</strong>
        </div>
        <div class="portal-ticket-status-overview">
            @foreach(\App\Enums\TicketStatus::cases() as $status)
                <span>
                    <i></i>
                    {{ $status->label() }}
                    <b>{{ number_format((int) $ticketStatusCounts->get($status->value, 0)) }}</b>
                </span>
            @endforeach
        </div>
    </section>

    <section class="portal-card overflow-hidden">
        <div class="portal-card-head">
            <div>
                <h2>همه درخواست‌ها</h2>
                <p>موارد جدید، در حال انجام، حل‌شده و بسته‌شده همگی در این لیست نگهداری می‌شوند.</p>
            </div>
        </div>

        <div class="portal-ticket-table">
            <div class="portal-ticket-table-head" aria-hidden="true">
                <span>درخواست</span>
                <span>وضعیت</span>
                <span>اولویت</span>
                <span>آخرین فعالیت</span>
                <span></span>
            </div>

            @forelse($tickets as $ticket)
                @php($unread = $ticket->hasUnreadStaffReply())
                <a href="{{ route('portal.tickets.show', $ticket) }}" class="portal-ticket-table-row {{ $unread ? 'unread' : '' }}">
                    <div class="portal-ticket-table-subject">
                        <div>
                            @if($unread)<span class="portal-unread-dot" aria-label="پاسخ خوانده‌نشده"></span>@endif
                            <strong>{{ $ticket->subject }}</strong>
                        </div>
                        <p>
                            <span>#{{ $ticket->id }}</span>
                            @if($ticket->latestPublicMessage)
                                <span>{{ \Illuminate\Support\Str::limit($ticket->latestPublicMessage->body, 88) }}</span>
                            @else
                                <span>برای مشاهده جزئیات وارد گفتگو شوید.</span>
                            @endif
                        </p>
                    </div>
                    <div class="portal-ticket-table-cell portal-ticket-status-cell">
                        <x-badge :type="$ticket->status->intent()">{{ $ticket->status->label() }}</x-badge>
                        @if($unread)<small>پاسخ جدید</small>@endif
                    </div>
                    <div class="portal-ticket-table-cell">
                        <x-badge :type="$ticket->priority->intent()">{{ $ticket->priority->label() }}</x-badge>
                    </div>
                    <div class="portal-ticket-table-cell portal-ticket-date-cell">
                        <time>{{ app(\App\Support\DatePresenter::class)->dateTime($ticket->updated_at) }}</time>
                    </div>
                    <div class="portal-ticket-table-action">
                        <span>مشاهده</span>
                        <b>‹</b>
                    </div>
                </a>
            @empty
                <div class="portal-empty-compact portal-empty-large">
                    <x-icon name="tickets" />
                    <strong>هنوز درخواستی ندارید</strong>
                    <p>برای ارتباط با تیم پشتیبانی اولین درخواست خود را ثبت کنید.</p>
                    <a class="btn btn-primary" href="{{ route('portal.tickets.create') }}">ثبت اولین درخواست</a>
                </div>
            @endforelse
        </div>

        @if($tickets->hasPages())
            <div class="portal-pagination">{{ $tickets->links() }}</div>
        @endif
    </section>
</x-layouts.portal>
