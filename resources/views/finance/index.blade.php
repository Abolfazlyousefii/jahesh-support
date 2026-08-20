<x-layouts.app title="مالی مشتریان">
    <x-page-header title="مالی مشتریان" description="مانده حساب، اسناد بدهکار/بستانکار و فیش‌های کارت به کارت مشتریان">
        <x-slot:actions>
            <div class="flex w-full flex-col gap-2 sm:w-auto sm:flex-row">
                @can('finance.review_payments')
                    <a href="{{ route('finance.receipts.index') }}" class="btn btn-secondary w-full sm:w-auto">
                        فیش‌های پرداخت
                        @if($metrics['pending_receipts'] > 0)
                            <span class="rounded-full bg-amber-100 px-2 py-0.5 text-xs text-amber-800">{{ $metrics['pending_receipts'] }}</span>
                        @endif
                    </a>
                @endcan
                @can('finance.manage_bank_accounts')
                    <a href="{{ route('finance.bank-accounts.index') }}" class="btn btn-primary w-full sm:w-auto">حساب‌های بانکی</a>
                @endcan
            </div>
        </x-slot:actions>
    </x-page-header>

    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        <section class="panel p-4 sm:p-5">
            <span class="text-xs text-gray-500">کل بدهکار ثبت‌شده</span>
            <strong class="mt-2 block text-lg sm:text-xl">{{ number_format($metrics['debit']) }} <small class="text-xs font-medium text-gray-500">تومان</small></strong>
        </section>
        <section class="panel p-4 sm:p-5">
            <span class="text-xs text-gray-500">کل بستانکار ثبت‌شده</span>
            <strong class="mt-2 block text-lg text-emerald-700 sm:text-xl">{{ number_format($metrics['credit']) }} <small class="text-xs font-medium text-gray-500">تومان</small></strong>
        </section>
        <section class="panel p-4 sm:p-5">
            <span class="text-xs text-gray-500">خالص مانده مشتریان</span>
            <strong class="mt-2 block text-lg sm:text-xl {{ $metrics['net'] > 0 ? 'text-rose-700' : ($metrics['net'] < 0 ? 'text-emerald-700' : '') }}">
                {{ number_format(abs($metrics['net'])) }} <small class="text-xs font-medium text-gray-500">تومان</small>
            </strong>
            <span class="mt-1 block text-xs text-gray-500">{{ $metrics['net'] > 0 ? 'بدهکار به مجموعه' : ($metrics['net'] < 0 ? 'بستانکار از مجموعه' : 'تسویه') }}</span>
        </section>
        <a href="{{ route('finance.receipts.index') }}" class="panel p-4 transition hover:border-amber-300 sm:p-5">
            <span class="text-xs text-gray-500">فیش‌های در انتظار بررسی</span>
            <strong class="mt-2 block text-lg text-amber-700 sm:text-xl">{{ $metrics['pending_receipts'] }} مورد</strong>
            <span class="mt-1 block text-xs text-gray-500">{{ number_format($metrics['pending_amount']) }} تومان</span>
        </a>
    </div>

    <section class="panel mt-4 overflow-hidden">
        <form method="GET" class="flex flex-col gap-3 border-b border-gray-100 p-4 sm:flex-row sm:items-end">
            <div class="flex-1">
                <label for="q" class="form-label">جستجوی مشتری</label>
                <input id="q" name="q" value="{{ $search }}" class="form-control" placeholder="نام، شرکت، شهر یا شماره تماس">
            </div>
            <div class="flex gap-2">
                <button class="btn btn-primary flex-1 sm:flex-none">جستجو</button>
                @if($search !== '')<a href="{{ route('finance.index') }}" class="btn btn-secondary flex-1 sm:flex-none">پاک کردن</a>@endif
            </div>
        </form>

        <div class="ui-table-wrap hidden md:block">
            <table class="ui-table min-w-[820px]">
                <thead class="bg-gray-50 text-xs text-gray-500">
                    <tr><th class="px-5 py-3">مشتری</th><th class="px-4 py-3">بدهکار</th><th class="px-4 py-3">بستانکار</th><th class="px-4 py-3">مانده</th><th class="px-4 py-3">فیش معلق</th><th class="px-5 py-3"></th></tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($customers as $customer)
                        @php($balance = (int)($customer->debit_total ?? 0) - (int)($customer->credit_total ?? 0))
                        <tr>
                            <td class="px-5 py-4"><strong class="block">{{ $customer->name }}</strong><span class="mt-1 block text-xs text-gray-500">{{ $customer->company_name ?: ($customer->primaryPhone?->phone ?: '—') }}</span></td>
                            <td class="px-4 py-4">{{ number_format((int)($customer->debit_total ?? 0)) }}</td>
                            <td class="px-4 py-4 text-emerald-700">{{ number_format((int)($customer->credit_total ?? 0)) }}</td>
                            <td class="px-4 py-4 font-bold {{ $balance > 0 ? 'text-rose-700' : ($balance < 0 ? 'text-emerald-700' : '') }}">{{ number_format(abs($balance)) }} <span class="text-xs font-normal">{{ $balance > 0 ? 'بدهکار' : ($balance < 0 ? 'بستانکار' : 'تسویه') }}</span></td>
                            <td class="px-4 py-4">@if($customer->pending_receipts_count)<span class="rounded-full bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-700">{{ $customer->pending_receipts_count }} مورد</span>@else<span class="text-gray-400">—</span>@endif</td>
                            <td class="px-5 py-4 text-left"><a href="{{ route('finance.customers.show', $customer) }}" class="font-semibold text-emerald-700">گردش حساب</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-5 py-10 text-center text-gray-500">مشتری‌ای پیدا نشد.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="divide-y divide-gray-100 md:hidden">
            @forelse($customers as $customer)
                @php($balance = (int)($customer->debit_total ?? 0) - (int)($customer->credit_total ?? 0))
                <a href="{{ route('finance.customers.show', $customer) }}" class="block p-4 active:bg-gray-50">
                    <div class="flex items-start justify-between gap-3"><div class="min-w-0"><strong class="block truncate">{{ $customer->name }}</strong><span class="mt-1 block truncate text-xs text-gray-500">{{ $customer->company_name ?: ($customer->primaryPhone?->phone ?: '—') }}</span></div>@if($customer->pending_receipts_count)<span class="shrink-0 rounded-full bg-amber-50 px-2 py-1 text-[11px] font-semibold text-amber-700">{{ $customer->pending_receipts_count }} فیش</span>@endif</div>
                    <div class="mt-3 flex items-end justify-between gap-3"><span class="text-xs text-gray-500">مانده حساب</span><strong class="text-sm {{ $balance > 0 ? 'text-rose-700' : ($balance < 0 ? 'text-emerald-700' : '') }}">{{ number_format(abs($balance)) }} تومان · {{ $balance > 0 ? 'بدهکار' : ($balance < 0 ? 'بستانکار' : 'تسویه') }}</strong></div>
                </a>
            @empty
                <div class="p-8 text-center text-sm text-gray-500">مشتری‌ای پیدا نشد.</div>
            @endforelse
        </div>

        @if($customers->hasPages())<div class="border-t border-gray-100 p-4">{{ $customers->links() }}</div>@endif
    </section>
</x-layouts.app>
