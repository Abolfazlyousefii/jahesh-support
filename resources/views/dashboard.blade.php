<x-layouts.app title="داشبورد">
    <x-page-header title="داشبورد" description="نمای کلی هسته مدیریت تیم جهش" />
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="panel p-5"><span class="text-sm text-gray-500">اعضای فعال</span><strong class="mt-2 block text-2xl">{{ $activeUsers }}</strong></div>
        <div class="panel p-5"><span class="text-sm text-gray-500">نقش‌های تعریف‌شده</span><strong class="mt-2 block text-2xl">{{ $rolesCount }}</strong></div>
        <div class="panel p-5"><span class="text-sm text-gray-500">تاریخ امروز</span><strong class="mt-2 block text-xl">{{ $today }}</strong></div>
        @can('customers.view')
            <div class="panel p-5"><span class="text-sm text-gray-500">مشتریان فعال</span><strong class="mt-2 block text-2xl">{{ $activeCustomers }}</strong></div>
        @endcan
    </div>
    @can('tasks.view')
        <section class="mt-5">
            <div class="mb-3 flex items-center justify-between"><h2 class="text-base font-bold">وضعیت تسک‌ها</h2><a href="{{ route('tasks.index') }}" class="text-sm font-semibold text-emerald-700">مشاهده تسک‌ها</a></div>
            <div class="grid gap-3 sm:grid-cols-3 lg:grid-cols-5">
                <div class="panel p-4"><span class="text-xs text-gray-500">تسک‌های امروز من</span><strong class="mt-2 block text-xl">{{ $taskMetrics['today'] }}</strong></div>
                <div class="panel p-4"><span class="text-xs text-gray-500">عقب‌افتاده من</span><strong class="mt-2 block text-xl">{{ $taskMetrics['overdue'] }}</strong></div>
                <div class="panel p-4"><span class="text-xs text-gray-500">در حال انجام من</span><strong class="mt-2 block text-xl">{{ $taskMetrics['inProgress'] }}</strong></div>
                @can('tasks.view_all')
                    <div class="panel p-4"><span class="text-xs text-gray-500">کل تسک‌های باز</span><strong class="mt-2 block text-xl">{{ $taskMetrics['teamOpen'] }}</strong></div>
                    <div class="panel p-4"><span class="text-xs text-gray-500">عقب‌افتاده تیم</span><strong class="mt-2 block text-xl">{{ $taskMetrics['teamOverdue'] }}</strong></div>
                @endcan
            </div>
        </section>

        <section class="panel mt-5 overflow-hidden">
            <div class="border-b border-gray-100 px-5 py-4"><h2 class="text-base font-bold">تسک‌های امروز</h2></div>
            @forelse($todayTasks as $task)
                <a href="{{ route('tasks.show', $task) }}" class="flex min-h-14 items-center justify-between gap-3 border-b border-gray-100 px-5 py-3 last:border-0 hover:bg-gray-50">
                    <span class="font-semibold">{{ $task->title }}</span>
                    <div class="flex shrink-0 gap-2"><x-badge :type="$task->priority->intent()">{{ $task->priority->label() }}</x-badge><x-badge :type="$task->status->intent()">{{ $task->status->label() }}</x-badge></div>
                </a>
            @empty
                <div class="px-5 py-7 text-center text-sm text-gray-500">برای امروز تسکی ندارید.</div>
            @endforelse
        </section>
    @endcan
    <div class="panel mt-5 p-5">
        <h2 class="mb-3 text-base font-bold">دسترسی سریع</h2>
        <div class="flex flex-wrap gap-2">
            @can('customers.create')<a class="btn btn-primary" href="{{ route('customers.create') }}">افزودن مشتری</a>@endcan
            @can('tasks.create')<a class="btn btn-primary" href="{{ route('tasks.create') }}">ایجاد تسک</a>@endcan
            @can('team.create')<a class="btn btn-primary" href="{{ route('team.create') }}">افزودن عضو</a>@endcan
            @can('roles.create')<a class="btn btn-secondary" href="{{ route('roles.create') }}">ایجاد نقش</a>@endcan
        </div>
    </div>
</x-layouts.app>
