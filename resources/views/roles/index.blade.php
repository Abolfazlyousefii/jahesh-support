<x-layouts.app title="نقش‌ها و دسترسی‌ها">
    <x-page-header title="نقش‌ها و دسترسی‌ها" description="نقش‌ها را بدون نیاز به کار با نام‌های فنی دسترسی مدیریت کنید.">
        <x-slot:actions>
            @can('roles.create')
                <a href="{{ route('roles.create') }}" class="btn btn-primary">ایجاد نقش</a>
            @endcan
        </x-slot:actions>
    </x-page-header>
    <div class="panel overflow-hidden">@if($roles->isEmpty())<x-empty-state message="هنوز نقشی ثبت نشده است." />@else<div class="ui-table-wrap"><table class="ui-table min-w-[620px]"><thead><tr><th class="p-4">عنوان</th><th class="p-4">نام فنی</th><th class="p-4">تعداد کاربران</th><th class="p-4">تعداد دسترسی</th><th class="p-4">عملیات</th></tr></thead><tbody class="divide-y divide-gray-100">@foreach($roles as $role)<tr><td class="p-4 font-semibold">{{ $role->title }} @if($role->is_system)<x-badge>سیستمی</x-badge>@endif</td><td class="p-4" dir="ltr">{{ $role->name }}</td><td class="p-4">{{ $role->users_count }}</td><td class="p-4">{{ $role->permissions_count }}</td><td class="p-4"><div class="flex gap-2">@can('roles.update')<a class="btn btn-secondary" href="{{ route('roles.edit', $role) }}">ویرایش</a>@endcan @can('roles.delete')@unless($role->is_system)<form method="POST" action="{{ route('roles.destroy', $role) }}" data-confirm="این نقش حذف شود؟">@csrf @method('DELETE')<x-button variant="danger">حذف</x-button></form>@endunless @endcan</div></td></tr>@endforeach</tbody></table></div><div class="border-t border-gray-100 p-4">{{ $roles->links() }}</div>@endif</div>
</x-layouts.app>
