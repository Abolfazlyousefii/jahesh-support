<x-layouts.app :title="$customer->name">
    <x-page-header :title="$customer->name" :description="$customer->company_name">
        <x-slot:actions>
            @can('customers.update')<a href="{{ route('customers.edit', $customer) }}" class="btn btn-primary">ویرایش مشتری</a>@endcan
        </x-slot:actions>
    </x-page-header>

    <div class="mb-4"><x-badge :type="$customer->is_active ? 'success' : 'danger'">{{ $customer->is_active ? 'فعال' : 'غیرفعال' }}</x-badge></div>

    <div class="grid gap-4 lg:grid-cols-3">
        <section class="panel p-5 lg:col-span-2">
            <h2 class="mb-4 text-base font-bold">اطلاعات اصلی</h2>
            <dl class="grid gap-5 sm:grid-cols-2">
                <div><dt class="text-xs text-gray-500">شهر</dt><dd class="mt-1">{{ $customer->city ?: '—' }}</dd></div>
                <div><dt class="text-xs text-gray-500">تاریخ ثبت</dt><dd class="mt-1">{{ app(\App\Support\DatePresenter::class)->date($customer->created_at) }}</dd></div>
                <div class="sm:col-span-2"><dt class="text-xs text-gray-500">آدرس</dt><dd class="mt-1 whitespace-pre-line">{{ $customer->address ?: '—' }}</dd></div>
                <div class="sm:col-span-2"><dt class="text-xs text-gray-500">یادداشت داخلی</dt><dd class="mt-1 whitespace-pre-line">{{ $customer->notes ?: '—' }}</dd></div>
            </dl>
        </section>

        <section class="panel p-5">
            <h2 class="mb-4 text-base font-bold">شماره‌های تماس</h2>
            <ul class="space-y-3">
                @foreach($customer->phones as $phone)
                    <li class="flex min-h-11 items-center justify-between rounded-lg border border-gray-100 px-3"><span dir="ltr">{{ $phone->phone }}</span>@if($phone->is_primary)<x-badge type="success">اصلی</x-badge>@endif</li>
                @endforeach
            </ul>
        </section>
    </div>

    @can('tickets.view')
        <section class="panel mt-4 overflow-hidden"><div class="flex items-center justify-between border-b border-gray-100 px-5 py-4"><h2 class="font-bold">تیکت‌های اخیر</h2><a href="{{ route('tickets.index', ['customer_id' => $customer->id]) }}" class="text-sm font-semibold text-emerald-700">مشاهده همه تیکت‌ها</a></div>@forelse($recentTickets as $ticket)<a href="{{ route('tickets.show', $ticket) }}" class="flex min-h-14 items-center justify-between gap-3 border-b border-gray-100 px-5 py-3 last:border-0"><span><strong>{{ $ticket->subject }}</strong><small class="mr-2 text-gray-400">#{{ $ticket->id }}</small></span><x-badge :type="$ticket->status->intent()">{{ $ticket->status->label() }}</x-badge></a>@empty<div class="px-5 py-7 text-center text-sm text-gray-500">تیکتی برای این مشتری وجود ندارد.</div>@endforelse</section>
    @endcan

    @can('customers.delete')
        <div class="mt-6 border-t border-gray-200 pt-5">
            <form method="POST" action="{{ route('customers.destroy', $customer) }}" onsubmit="return confirm('این مشتری حذف شود؟ این عملیات به‌صورت حذف نرم انجام می‌شود.')">
                @csrf @method('DELETE')
                <x-button variant="danger">حذف مشتری</x-button>
            </form>
        </div>
    @endcan
</x-layouts.app>
