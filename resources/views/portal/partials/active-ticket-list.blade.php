@forelse($activeTickets as $ticket)
    @php($unread = $ticket->hasUnreadStaffReply())
    <a href="{{ route('portal.tickets.show', $ticket) }}" class="portal-active-ticket-row {{ $unread ? 'unread' : '' }}">
        <div class="portal-active-ticket-main">
            <div class="portal-active-ticket-title">
                @if($unread)<span class="portal-unread-dot" aria-label="پاسخ خوانده‌نشده"></span>@endif
                <strong>{{ $ticket->subject }}</strong>
            </div>
            <div class="portal-active-ticket-sub">
                <span>#{{ $ticket->id }}</span>
                @if($ticket->latestPublicMessage)
                    <span>{{ \Illuminate\Support\Str::limit($ticket->latestPublicMessage->body, 90) }}</span>
                @else
                    <span>برای مشاهده جزئیات وارد گفتگو شوید.</span>
                @endif
            </div>
        </div>
        <div class="portal-active-ticket-status">
            <x-badge :type="$ticket->status->intent()">{{ $ticket->status->label() }}</x-badge>
            @if($unread)<span class="portal-new-reply">پاسخ جدید</span>@endif
        </div>
        <div class="portal-active-ticket-time">
            <time>{{ app(\App\Support\DatePresenter::class)->dateTime($ticket->updated_at) }}</time>
            <span>مشاهده درخواست</span>
        </div>
    </a>
@empty
    <div class="portal-empty-compact portal-active-empty">
        <x-icon name="tickets" />
        <strong>تیکت بازی ندارید</strong>
        <p>در حال حاضر همه درخواست‌های شما تعیین تکلیف شده‌اند.</p>
        <a href="{{ route('portal.tickets.create') }}" class="btn btn-primary">ثبت درخواست جدید</a>
    </div>
@endforelse
