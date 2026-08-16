<x-layouts.app title="مشتریان">
    <x-page-header title="مشتریان" description="مدیریت اطلاعات و شماره‌های تماس مشتریان">
        <x-slot:actions>
            @can('customers.create')<a href="{{ route('customers.create') }}" class="btn btn-primary">افزودن مشتری</a>@endcan
        </x-slot:actions>
    </x-page-header>

    <form method="GET" class="panel mb-4 grid gap-3 p-3 sm:grid-cols-[1fr_180px_auto]">
        <div>
            <label for="customer-search" class="sr-only">جستجوی مشتری</label>
            <input id="customer-search" class="form-control" name="q" value="{{ $search }}" placeholder="جستجو بر اساس نام، مجموعه، موبایل یا شهر">
        </div>
        <div>
            <label for="customer-status" class="sr-only">وضعیت</label>
            <select id="customer-status" name="status" class="form-control">
                <option value="all" @selected($status === 'all')>همه وضعیت‌ها</option>
                <option value="active" @selected($status === 'active')>فعال</option>
                <option value="inactive" @selected($status === 'inactive')>غیرفعال</option>
            </select>
        </div>
        <x-button variant="secondary">جستجو</x-button>
    </form>

    <div class="panel overflow-hidden">
        @if($customers->isEmpty())
            <x-empty-state :message="$search !== '' || $status !== 'all' ? 'مشتری مطابق جستجو پیدا نشد.' : 'هنوز مشتری‌ای ثبت نشده است.'">
                <x-slot:action>
                    @can('customers.create')<a class="btn btn-primary" href="{{ route('customers.create') }}">افزودن اولین مشتری</a>@endcan
                </x-slot:action>
            </x-empty-state>
        @else
            <div class="hidden overflow-x-auto md:block">
                <table class="w-full min-w-[900px] text-right">
                    <thead class="bg-gray-50 text-xs text-gray-500"><tr><th class="p-4">نام مشتری</th><th class="p-4">مجموعه</th><th class="p-4">شماره اصلی</th><th class="p-4">شهر</th><th class="p-4">وضعیت</th><th class="p-4">تاریخ ثبت</th><th class="p-4">عملیات</th></tr></thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($customers as $customer)
                            <tr>
                                <td class="p-4 font-semibold"><a class="hover:text-emerald-700" href="{{ route('customers.show', $customer) }}">{{ $customer->name }}</a></td>
                                <td class="p-4 text-gray-600">{{ $customer->company_name ?: '—' }}</td>
                                <td class="p-4" dir="ltr">{{ $customer->primaryPhone?->phone }}</td>
                                <td class="p-4 text-gray-600">{{ $customer->city ?: '—' }}</td>
                                <td class="p-4"><x-badge :type="$customer->is_active ? 'success' : 'danger'">{{ $customer->is_active ? 'فعال' : 'غیرفعال' }}</x-badge></td>
                                <td class="p-4">{{ app(\App\Support\DatePresenter::class)->date($customer->created_at) }}</td>
                                <td class="p-4"><div class="flex gap-2"><a class="btn btn-secondary" href="{{ route('customers.show', $customer) }}">مشاهده</a>@can('customers.update')<a class="btn btn-secondary" href="{{ route('customers.edit', $customer) }}">ویرایش</a>@endcan</div></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="divide-y divide-gray-100 md:hidden">
                @foreach($customers as $customer)
                    <a href="{{ route('customers.show', $customer) }}" class="block min-h-24 p-4 active:bg-gray-50">
                        <div class="flex items-start justify-between gap-3"><strong>{{ $customer->name }}</strong><x-badge :type="$customer->is_active ? 'success' : 'danger'">{{ $customer->is_active ? 'فعال' : 'غیرفعال' }}</x-badge></div>
                        @if($customer->company_name)<p class="mt-1 text-sm text-gray-500">{{ $customer->company_name }}</p>@endif
                        <div class="mt-3 flex flex-wrap gap-x-5 gap-y-1 text-sm"><span dir="ltr">{{ $customer->primaryPhone?->phone }}</span><span class="text-gray-500">{{ $customer->city ?: 'شهر ثبت نشده' }}</span></div>
                    </a>
                @endforeach
            </div>
            <div class="border-t border-gray-100 p-4">{{ $customers->links() }}</div>
        @endif
    </div>
</x-layouts.app>
