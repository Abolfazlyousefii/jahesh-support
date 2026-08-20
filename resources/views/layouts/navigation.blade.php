@php
    $activeMenu = match (true) {
        request()->routeIs('tasks.*') => 'tasks',
        request()->routeIs('tickets.*') => 'tickets',
        request()->routeIs('customers.*') => 'customers',
        request()->routeIs('finance.*') => 'finance',
        request()->routeIs('team.*', 'roles.*', 'activity.*') => 'team',
        request()->routeIs('settings.*') => 'settings',
        default => null,
    };

    $taskView = request()->string('view')->toString() === 'list' ? 'list' : 'board';
    $taskScope = request()->string('scope')->toString() === 'all' ? 'all' : 'mine';
    $ticketScope = request()->string('scope')->toString() === 'mine'
        ? 'mine'
        : (auth()->user()->can('tickets.view_all') ? 'all' : 'mine');
    $ticketAttention = request()->boolean('unread');
    $pendingReceipts = request()->string('status')->toString() === 'pending';
@endphp

<nav
    class="sidebar-nav"
    aria-label="پیمایش پنل مدیریت"
    x-data="{
        pinnedMenu: @js($activeMenu),
        hoverMenu: null,
        isOpen(menu) { return this.pinnedMenu === menu || this.hoverMenu === menu },
        toggle(menu) { this.pinnedMenu = this.pinnedMenu === menu ? null : menu }
    }"
>
    @can('dashboard.view')
        <x-navigation.item
            :href="route('dashboard')"
            label="داشبورد"
            icon="dashboard"
            :active="request()->routeIs('dashboard')"
            :top-level="true"
        />
    @endcan

    @can('tasks.view')
        <x-navigation.group name="tasks" label="تسک‌ها" icon="tasks" :active="request()->routeIs('tasks.*')">
            <x-slot:badge>
                @if(($navigationMetrics['overdue_tasks'] ?? 0) > 0)
                    <x-navigation.badge tone="danger">{{ min($navigationMetrics['overdue_tasks'], 99) }}</x-navigation.badge>
                @endif
            </x-slot:badge>

            @can('tasks.view_all')
                <x-navigation.item
                    :href="route('tasks.index', ['scope' => 'all', 'view' => 'list'])"
                    label="همه تسک‌ها"
                    :active="request()->routeIs('tasks.index') && $taskView === 'list' && $taskScope === 'all'"
                />
            @endcan
            <x-navigation.item
                :href="route('tasks.index', ['scope' => 'mine', 'view' => 'list'])"
                label="تسک‌های من"
                :active="request()->routeIs('tasks.index') && $taskView === 'list' && $taskScope === 'mine'"
            />
            @can('tasks.create')
                <x-navigation.item :href="route('tasks.create')" label="ایجاد تسک" :active="request()->routeIs('tasks.create')" />
            @endcan
            <x-navigation.item
                :href="route('tasks.index', ['scope' => $taskScope, 'view' => 'board'])"
                label="کانبان"
                :active="request()->routeIs('tasks.index') && $taskView === 'board'"
            />
        </x-navigation.group>
    @endcan

    @can('tickets.view')
        <x-navigation.group name="tickets" label="تیکت‌ها" icon="tickets" :active="request()->routeIs('tickets.*')">
            <x-slot:badge>
                @if(($navigationMetrics['attention_tickets'] ?? 0) > 0)
                    <x-navigation.badge tone="warning">{{ min($navigationMetrics['attention_tickets'], 99) }}</x-navigation.badge>
                @endif
            </x-slot:badge>

            @can('tickets.view_all')
                <x-navigation.item
                    :href="route('tickets.index', ['scope' => 'all'])"
                    label="همه تیکت‌ها"
                    :active="request()->routeIs('tickets.index') && $ticketScope === 'all' && ! $ticketAttention"
                />
            @endcan
            <x-navigation.item
                :href="route('tickets.index', ['scope' => 'mine'])"
                label="تیکت‌های من"
                :active="request()->routeIs('tickets.index') && $ticketScope === 'mine' && ! $ticketAttention"
            />
            <x-navigation.item
                :href="route('tickets.index', ['scope' => $ticketScope, 'unread' => 1])"
                label="نیازمند پاسخ"
                :active="request()->routeIs('tickets.index') && $ticketAttention"
            />
        </x-navigation.group>
    @endcan

    @can('customers.view')
        <x-navigation.group name="customers" label="مشتریان" icon="customers" :active="request()->routeIs('customers.*')">
            <x-navigation.item :href="route('customers.index')" label="همه مشتریان" :active="request()->routeIs('customers.index')" />
            @can('customers.create')
                <x-navigation.item :href="route('customers.create')" label="افزودن مشتری" :active="request()->routeIs('customers.create')" />
            @endcan
        </x-navigation.group>
    @endcan

    @can('finance.view')
        <x-navigation.group name="finance" label="مالی" icon="finance" :active="request()->routeIs('finance.*')">
            <x-slot:badge>
                @if(($navigationMetrics['pending_receipts'] ?? 0) > 0)
                    <x-navigation.badge tone="violet">{{ min($navigationMetrics['pending_receipts'], 99) }}</x-navigation.badge>
                @endif
            </x-slot:badge>

            <x-navigation.item
                :href="route('finance.index')"
                label="مالی مشتریان"
                :active="request()->routeIs('finance.index', 'finance.customers.*')"
            />
            <x-navigation.item
                :href="route('finance.receipts.index')"
                label="فیش‌های پرداخت"
                :active="request()->routeIs('finance.receipts.*') && ! $pendingReceipts"
            />
            @can('finance.review_payments')
                <x-navigation.item
                    :href="route('finance.receipts.index', ['status' => 'pending'])"
                    label="فیش‌های در انتظار"
                    :active="request()->routeIs('finance.receipts.index') && $pendingReceipts"
                />
            @endcan
            @can('finance.manage_bank_accounts')
                <x-navigation.item :href="route('finance.bank-accounts.index')" label="حساب‌های بانکی" :active="request()->routeIs('finance.bank-accounts.*')" />
            @endcan
        </x-navigation.group>
    @endcan

    @canany(['team.view', 'roles.view', 'activity.view'])
        <x-navigation.group name="team" label="تیم" icon="users" :active="request()->routeIs('team.*', 'roles.*', 'activity.*')">
            @can('team.view')<x-navigation.item :href="route('team.index')" label="اعضای تیم" :active="request()->routeIs('team.*')" />@endcan
            @can('roles.view')<x-navigation.item :href="route('roles.index')" label="نقش‌ها و دسترسی‌ها" :active="request()->routeIs('roles.*')" />@endcan
            @can('activity.view')<x-navigation.item :href="route('activity.index')" label="گزارش فعالیت‌ها" :active="request()->routeIs('activity.*')" />@endcan
        </x-navigation.group>
    @endcanany

    @canany(['settings.general.manage', 'settings.sms.manage'])
        <x-navigation.group name="settings" label="تنظیمات" icon="settings" :active="request()->routeIs('settings.*')">
            @can('settings.general.manage')<x-navigation.item :href="route('settings.general.index')" label="تنظیمات عمومی" :active="request()->routeIs('settings.general.*')" />@endcan
            @can('settings.sms.manage')<x-navigation.item :href="route('settings.sms.index')" label="پیامک" :active="request()->routeIs('settings.sms.*')" />@endcan
        </x-navigation.group>
    @endcanany
</nav>
