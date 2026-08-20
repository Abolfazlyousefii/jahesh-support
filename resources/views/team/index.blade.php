<x-layouts.app title="اعضای تیم">
    <x-page-header title="اعضای تیم" description="مدیریت کاربران داخلی پنل">
        <x-slot:actions>
            @can('team.create')
                <a href="{{ route('team.create') }}" class="btn btn-primary">افزودن عضو</a>
            @endcan
        </x-slot:actions>
    </x-page-header>
    <form method="GET" class="panel mb-4 flex flex-col gap-2 p-3 sm:flex-row"><input class="form-control" name="search" value="{{ $search }}" placeholder="جستجو بر اساس نام یا شماره موبایل"><button class="btn btn-secondary sm:w-28">جستجو</button></form>
    <div class="panel overflow-hidden">
        @if($users->isEmpty())
            <x-empty-state message="هنوز عضوی ثبت نشده است.">
                <x-slot:action>
                    @can('team.create')
                        <a class="btn btn-primary" href="{{ route('team.create') }}">افزودن عضو</a>
                    @endcan
                </x-slot:action>
            </x-empty-state>
        @else
            <div class="ui-table-wrap"><table class="ui-table min-w-[780px]"><thead><tr><th class="p-4">نام</th><th class="p-4">شماره موبایل</th><th class="p-4">نقش‌ها</th><th class="p-4">وضعیت</th><th class="p-4">تاریخ ایجاد</th><th class="p-4">عملیات</th></tr></thead><tbody class="divide-y divide-gray-100">
                @foreach($users as $user)<tr><td class="p-4 font-semibold">{{ $user->name }}</td><td class="p-4" dir="ltr">{{ $user->phone }}</td><td class="p-4">{{ $user->roles->pluck('title')->join('، ') ?: '—' }}</td><td class="p-4"><x-badge :type="$user->is_active ? 'success' : 'danger'">{{ $user->is_active ? 'فعال' : 'غیرفعال' }}</x-badge></td><td class="p-4">{{ app(\App\Support\DatePresenter::class)->date($user->created_at) }}</td><td class="p-4"><div class="flex gap-2">@can('team.update')<a class="btn btn-secondary" href="{{ route('team.edit', $user) }}">ویرایش</a>@endcan @can('team.delete')<form method="POST" action="{{ route('team.destroy', $user) }}" data-confirm="این عضو حذف شود؟">@csrf @method('DELETE')<x-button variant="danger">حذف</x-button></form>@endcan</div></td></tr>@endforeach
            </tbody></table></div>
            <div class="border-t border-gray-100 p-4">{{ $users->links() }}</div>
        @endif
    </div>
</x-layouts.app>
