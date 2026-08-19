<nav class="space-y-1">
    @can('dashboard.view')<a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}"><x-icon name="dashboard" />داشبورد</a>@endcan
    @can('tasks.view')<a href="{{ route('tasks.index') }}" class="nav-link {{ request()->routeIs('tasks.*') ? 'active' : '' }}"><x-icon name="tasks" />تسک‌ها</a>@endcan
    @can('tickets.view')<a href="{{ route('tickets.index') }}" class="nav-link {{ request()->routeIs('tickets.*') ? 'active' : '' }}"><x-icon name="tickets" />تیکت‌ها</a>@endcan
    @can('finance.view')<a href="{{ route('finance.index') }}" class="nav-link {{ request()->routeIs('finance.*') ? 'active' : '' }}"><x-icon name="finance" />مالی مشتریان</a>@endcan
    @can('team.view')<a href="{{ route('team.index') }}" class="nav-link {{ request()->routeIs('team.*') ? 'active' : '' }}"><x-icon name="users" />اعضای تیم</a>@endcan
    @can('roles.view')<a href="{{ route('roles.index') }}" class="nav-link {{ request()->routeIs('roles.*') ? 'active' : '' }}"><x-icon name="shield" />نقش‌ها و دسترسی‌ها</a>@endcan
    @can('customers.view')<a href="{{ route('customers.index') }}" class="nav-link {{ request()->routeIs('customers.*') ? 'active' : '' }}"><x-icon name="customers" />مشتریان</a>@endcan
    @can('activity.view')<a href="{{ route('activity.index') }}" class="nav-link {{ request()->routeIs('activity.*') ? 'active' : '' }}"><x-icon name="activity" />گزارش فعالیت‌ها</a>@endcan
    @canany(['settings.general.manage', 'settings.sms.manage'])
        @php($settingsHome = auth()->user()->can('settings.general.manage') ? route('settings.general.index') : route('settings.sms.index'))
        <a href="{{ $settingsHome }}" class="nav-link {{ request()->routeIs('settings.*') ? 'active' : '' }}"><x-icon name="settings" />تنظیمات</a>
    @endcanany
</nav>
