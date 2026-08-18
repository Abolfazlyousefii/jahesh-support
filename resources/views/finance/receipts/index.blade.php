<x-layouts.app title="فیش‌های پرداخت">
    <x-page-header title="فیش‌های پرداخت" description="بررسی پرداخت‌های کارت به کارت مشتریان">
        <x-slot:actions><a href="{{ route('finance.index') }}" class="btn btn-secondary">بازگشت به مالی</a></x-slot:actions>
    </x-page-header>

    <section class="panel overflow-hidden">
        <form method="GET" class="grid gap-3 border-b border-gray-100 p-4 sm:grid-cols-[1fr_auto] sm:items-end">
            <div><label class="form-label">جستجو</label><input name="q" value="{{ $search }}" class="form-control" placeholder="نام مشتری یا شماره پیگیری"></div>
            <button class="btn btn-primary">جستجو</button>
            <div class="flex flex-wrap gap-2 sm:col-span-2">
                <a href="{{ route('finance.receipts.index', ['status' => 'all', 'q' => $search ?: null]) }}" class="rounded-full border px-3 py-2 text-xs font-semibold {{ $status === null ? 'border-emerald-300 bg-emerald-50 text-emerald-800' : 'border-gray-200 bg-white text-gray-600' }}">همه</a>
                @foreach($statuses as $item)
                    <a href="{{ route('finance.receipts.index', ['status' => $item->value, 'q' => $search ?: null]) }}" class="rounded-full border px-3 py-2 text-xs font-semibold {{ $status === $item ? 'border-emerald-300 bg-emerald-50 text-emerald-800' : 'border-gray-200 bg-white text-gray-600' }}">{{ $item->label() }} @if($item === \App\Enums\PaymentReceiptStatus::Pending && $pendingCount)<span class="mr-1">({{ $pendingCount }})</span>@endif</a>
                @endforeach
            </div>
        </form>

        <div class="divide-y divide-gray-100">
            @forelse($receipts as $receipt)
                <a href="{{ route('finance.receipts.show', $receipt) }}" class="block p-4 hover:bg-gray-50 sm:px-5">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div class="min-w-0"><div class="flex items-center gap-2"><strong class="truncate">{{ $receipt->customer->name }}</strong><x-badge :type="$receipt->status->intent()">{{ $receipt->status->label() }}</x-badge></div><span class="mt-1 block text-xs text-gray-500">{{ $receipt->customer->company_name ?: ($receipt->customer->primaryPhone?->phone ?: '—') }} · {{ app(\App\Support\DatePresenter::class)->date($receipt->paid_at) }}</span></div>
                        <div class="flex items-end justify-between gap-4 sm:block sm:text-left"><span class="text-xs text-gray-500 sm:hidden">مبلغ</span><strong class="text-base">{{ number_format($receipt->amount) }} تومان</strong><span class="mt-1 hidden text-xs text-gray-500 sm:block">{{ $receipt->bankAccount?->bank_name ?: 'حساب نامشخص' }}</span></div>
                    </div>
                </a>
            @empty
                <div class="p-10 text-center text-sm text-gray-500">فیشی در این وضعیت وجود ندارد.</div>
            @endforelse
        </div>
        @if($receipts->hasPages())<div class="border-t border-gray-100 p-4">{{ $receipts->links() }}</div>@endif
    </section>
</x-layouts.app>
