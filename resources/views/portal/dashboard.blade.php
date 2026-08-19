<x-layouts.portal title="خانه">
    <section class="portal-page-head">
        <div>
            <span class="portal-eyebrow">سلام {{ $customer->name }}</span>
            <h1>{{ $generalSettings['portal.welcome_text'] ?? 'خوش آمدید به پنل پشتیبانی جهش' }}</h1>
            <p>درخواست‌های پشتیبانی باز و وضعیت مالی حساب شما از این بخش در دسترس است.</p>
        </div>
        <a href="{{ route('portal.tickets.create') }}" class="btn btn-primary portal-cta">ثبت درخواست جدید</a>
    </section>

    <section class="portal-stats">
        <a href="{{ route('portal.finance.index') }}" class="portal-stat-card">
            <span class="portal-stat-icon"><x-icon name="finance" /></span>
            <span class="portal-stat-copy">
                <small>مانده حساب</small>
                <strong class="{{ $financeSummary['balance_kind'] === 'debit' ? 'text-rose-700' : ($financeSummary['balance_kind'] === 'credit' ? 'text-emerald-700' : '') }}">{{ number_format($financeSummary['balance_abs']) }} تومان</strong>
                <span>{{ $financeSummary['balance_kind'] === 'debit' ? 'بدهکار' : ($financeSummary['balance_kind'] === 'credit' ? 'بستانکار' : 'تسویه') }}</span>
            </span>
        </a>

        <a href="{{ route('portal.tickets.index') }}" class="portal-stat-card">
            <span class="portal-stat-icon"><x-icon name="tickets" /></span>
            <span class="portal-stat-copy">
                <small>تیکت‌های باز</small>
                <strong><span data-active-ticket-count>{{ number_format($activeTicketCount) }}</span> درخواست</strong>
                <span><span data-unread-ticket-count>{{ number_format($unreadTickets) }}</span> پاسخ خوانده‌نشده</span>
            </span>
        </a>

        <a href="{{ route('portal.finance.index') }}#receipts" class="portal-stat-card">
            <span class="portal-stat-icon"><x-icon name="upload" /></span>
            <span class="portal-stat-copy">
                <small>پرداخت در بررسی</small>
                <strong>{{ $financeSummary['pending_receipts'] }} فیش</strong>
                <span>{{ number_format($financeSummary['pending_amount'] ?? 0) }} تومان</span>
            </span>
        </a>
    </section>

    <div class="portal-dashboard-grid portal-dashboard-grid-tickets">
        <section class="portal-card overflow-hidden" data-live-ticket-panel data-endpoint="{{ route('portal.dashboard.active-tickets') }}">
            <div class="portal-card-head portal-live-head">
                <div>
                    <div class="portal-live-title-line">
                        <h2>تیکت‌های باز</h2>
                        <span class="portal-live-indicator" data-live-indicator>
                            <i></i>
                            <span data-live-status>به‌روزرسانی خودکار</span>
                        </span>
                    </div>
                    <p>درخواست‌هایی که هنوز حل یا بسته نشده‌اند</p>
                </div>
                <a href="{{ route('portal.tickets.index') }}">مشاهده همه تیکت‌ها</a>
            </div>

            <div class="portal-active-ticket-list" data-live-ticket-list>
                @include('portal.partials.active-ticket-list', ['activeTickets' => $activeTickets])
            </div>
        </section>

        <aside class="portal-card portal-quick-card">
            <div class="portal-card-head portal-card-head-simple">
                <div>
                    <h2>دسترسی سریع</h2>
                    <p>کارهای پراستفاده حساب شما</p>
                </div>
            </div>
            <div class="portal-quick-links">
                <a href="{{ route('portal.tickets.create') }}">
                    <span><x-icon name="tickets" />ثبت درخواست پشتیبانی</span>
                    <b>‹</b>
                </a>
                <a href="{{ route('portal.finance.index') }}">
                    <span><x-icon name="finance" />مشاهده حساب مالی</span>
                    <b>‹</b>
                </a>
                <a href="{{ route('portal.profile') }}">
                    <span><x-icon name="customers" />اطلاعات حساب من</span>
                    <b>‹</b>
                </a>
            </div>
            <div class="portal-soft-note">فقط تیکت‌های باز در داشبورد نمایش داده می‌شوند. آرشیو کامل درخواست‌ها، شامل موارد حل‌شده و بسته‌شده، همیشه از بخش تیکت‌ها در دسترس است.</div>
        </aside>
    </div>

    <script>
        (() => {
            const panel = document.querySelector('[data-live-ticket-panel]');
            if (!panel) return;

            const endpoint = panel.dataset.endpoint;
            const list = panel.querySelector('[data-live-ticket-list]');
            const indicator = panel.querySelector('[data-live-indicator]');
            const status = panel.querySelector('[data-live-status]');
            const activeCount = document.querySelector('[data-active-ticket-count]');
            const unreadCount = document.querySelector('[data-unread-ticket-count]');
            let refreshing = false;

            const formatNumber = (value) => new Intl.NumberFormat('fa-IR').format(Number(value || 0));

            async function refreshActiveTickets() {
                if (refreshing || document.hidden) return;
                refreshing = true;
                indicator?.classList.add('loading');
                if (status) status.textContent = 'در حال بررسی...';

                try {
                    const response = await fetch(endpoint, {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        credentials: 'same-origin',
                    });

                    if (!response.ok) throw new Error('refresh_failed');

                    const data = await response.json();
                    if (typeof data.html === 'string') list.innerHTML = data.html;
                    if (activeCount) activeCount.textContent = formatNumber(data.active_count);
                    if (unreadCount) unreadCount.textContent = formatNumber(data.unread_count);
                    indicator?.classList.remove('error');
                    if (status) status.textContent = 'به‌روز';
                } catch (error) {
                    indicator?.classList.add('error');
                    if (status) status.textContent = 'اتصال برقرار نشد';
                } finally {
                    indicator?.classList.remove('loading');
                    refreshing = false;
                }
            }

            const timer = window.setInterval(refreshActiveTickets, 20000);
            document.addEventListener('visibilitychange', () => {
                if (!document.hidden) refreshActiveTickets();
            });
            window.addEventListener('pageshow', refreshActiveTickets);
            window.addEventListener('beforeunload', () => window.clearInterval(timer), { once: true });
        })();
    </script>
</x-layouts.portal>
