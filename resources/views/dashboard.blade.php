<x-layouts.app title="داشبورد">
    <x-page-header title="داشبورد" description="نمای کلی هسته مدیریت تیم جهش" />
    <div class="grid gap-4 sm:grid-cols-3">
        <div class="panel p-5"><span class="text-sm text-gray-500">اعضای فعال</span><strong class="mt-2 block text-2xl">{{ $activeUsers }}</strong></div>
        <div class="panel p-5"><span class="text-sm text-gray-500">نقش‌های تعریف‌شده</span><strong class="mt-2 block text-2xl">{{ $rolesCount }}</strong></div>
        <div class="panel p-5"><span class="text-sm text-gray-500">تاریخ امروز</span><strong class="mt-2 block text-xl">{{ $today }}</strong></div>
    </div>
    <div class="panel mt-5 p-5"><h2 class="mb-3 text-base font-bold">دسترسی سریع</h2><div class="flex flex-wrap gap-2">@can('team.create')<a class="btn btn-primary" href="{{ route('team.create') }}">افزودن عضو</a>@endcan @can('roles.create')<a class="btn btn-secondary" href="{{ route('roles.create') }}">ایجاد نقش</a>@endcan</div></div>
</x-layouts.app>
