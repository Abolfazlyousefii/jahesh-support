<nav class="space-y-1">
    @can('dashboard.view')<a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}"><x-icon name="dashboard" />داشبورد</a>@endcan
    @can('team.view')<a href="{{ route('team.index') }}" class="nav-link {{ request()->routeIs('team.*') ? 'active' : '' }}"><x-icon name="users" />اعضای تیم</a>@endcan
    @can('roles.view')<a href="{{ route('roles.index') }}" class="nav-link {{ request()->routeIs('roles.*') ? 'active' : '' }}"><x-icon name="shield" />نقش‌ها و دسترسی‌ها</a>@endcan
    @can('customers.view')<a href="{{ route('customers.index') }}" class="nav-link {{ request()->routeIs('customers.*') ? 'active' : '' }}"><x-icon name="customers" />مشتریان</a>@endcan
</nav>
