<x-layouts.app :title="'مالی '.$customer->name">
    <x-page-header :title="'حساب مالی '.$customer->name" :description="$customer->company_name ?: 'گردش بدهکار و بستانکار مشتری'">
        <x-slot:actions>
            <div class="flex w-full flex-col gap-2 sm:w-auto sm:flex-row">
                <a href="{{ route('customers.show', $customer) }}" class="btn btn-secondary w-full sm:w-auto">پروفایل مشتری</a>
                @can('finance.create_entry')<button type="button" class="btn btn-primary w-full sm:w-auto" x-data @click="$dispatch('open-finance-entry')">+ سند جدید</button>@endcan
            </div>
        </x-slot:actions>
    </x-page-header>

    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        <section class="panel p-4 sm:p-5">
            <span class="text-xs text-gray-500">مانده فعلی</span>
            <strong class="mt-2 block text-xl {{ $summary['balance_kind'] === 'debit' ? 'text-rose-700' : ($summary['balance_kind'] === 'credit' ? 'text-emerald-700' : '') }}">{{ number_format($summary['balance_abs']) }} <small class="text-xs font-medium text-gray-500">تومان</small></strong>
            <span class="mt-1 block text-xs text-gray-500">{{ $summary['balance_kind'] === 'debit' ? 'مشتری بدهکار است' : ($summary['balance_kind'] === 'credit' ? 'مشتری بستانکار است' : 'حساب تسویه است') }}</span>
        </section>
        <section class="panel p-4 sm:p-5"><span class="text-xs text-gray-500">جمع بدهکار</span><strong class="mt-2 block text-lg">{{ number_format($summary['debit']) }} تومان</strong></section>
        <section class="panel p-4 sm:p-5"><span class="text-xs text-gray-500">جمع بستانکار</span><strong class="mt-2 block text-lg text-emerald-700">{{ number_format($summary['credit']) }} تومان</strong></section>
        <section class="panel p-4 sm:p-5"><span class="text-xs text-gray-500">فیش در انتظار</span><strong class="mt-2 block text-lg text-amber-700">{{ $summary['pending_receipts'] }} مورد</strong><span class="mt-1 block text-xs text-gray-500">{{ number_format($summary['pending_amount']) }} تومان</span></section>
    </div>

    <section class="panel mt-4 overflow-hidden">
        <div class="flex items-center justify-between border-b border-gray-100 px-4 py-4 sm:px-5"><div><h2 class="font-bold">گردش حساب</h2><p class="mt-1 text-xs text-gray-500">فقط اسناد غیرابطال‌شده در مانده حساب محاسبه می‌شوند.</p></div></div>

        <div class="ui-table-wrap hidden md:block">
            <table class="ui-table min-w-[900px]">
                <thead class="bg-gray-50 text-xs text-gray-500"><tr><th class="px-5 py-3">تاریخ</th><th class="px-4 py-3">شرح</th><th class="px-4 py-3">بدهکار</th><th class="px-4 py-3">بستانکار</th><th class="px-4 py-3">مرجع</th><th class="px-5 py-3">ثبت‌کننده / وضعیت</th></tr></thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($entries as $entry)
                        <tr class="{{ $entry->isVoided() ? 'bg-gray-50 text-gray-400' : '' }}">
                            <td class="whitespace-nowrap px-5 py-4">{{ app(\App\Support\DatePresenter::class)->date($entry->entry_date) }}</td>
                            <td class="px-4 py-4"><strong class="block font-medium {{ $entry->isVoided() ? 'line-through' : '' }}">{{ $entry->description }}</strong><span class="mt-1 block text-xs text-gray-400">{{ $entry->source === 'payment_receipt' ? 'ثبت خودکار از فیش پرداخت' : 'سند دستی' }}</span></td>
                            <td class="px-4 py-4">{{ $entry->type === \App\Enums\LedgerEntryType::Debit ? number_format($entry->amount) : '—' }}</td>
                            <td class="px-4 py-4 text-emerald-700">{{ $entry->type === \App\Enums\LedgerEntryType::Credit ? number_format($entry->amount) : '—' }}</td>
                            <td class="px-4 py-4">{{ $entry->reference ?: '—' }}</td>
                            <td class="px-5 py-4"><span class="block text-xs">{{ $entry->creator?->name ?: 'سیستم' }}</span>@if($entry->isVoided())<span class="mt-1 block text-xs font-semibold text-rose-600">ابطال شده</span>@elseif(auth()->user()->can('finance.void_entry'))<button type="button" class="mt-1 text-xs font-semibold text-rose-600" x-data @click="$dispatch('open-void-entry', { id: {{ $entry->id }}, description: @js($entry->description) })">ابطال سند</button>@endif</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-5 py-10 text-center text-gray-500">هنوز سند مالی ثبت نشده است.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="divide-y divide-gray-100 md:hidden">
            @forelse($entries as $entry)
                <article class="p-4 {{ $entry->isVoided() ? 'bg-gray-50 text-gray-400' : '' }}">
                    <div class="flex items-start justify-between gap-3"><div class="min-w-0"><strong class="block {{ $entry->isVoided() ? 'line-through' : '' }}">{{ $entry->description }}</strong><span class="mt-1 block text-xs text-gray-500">{{ app(\App\Support\DatePresenter::class)->date($entry->entry_date) }} · {{ $entry->source === 'payment_receipt' ? 'فیش پرداخت' : 'سند دستی' }}</span></div><x-badge :type="$entry->isVoided() ? 'neutral' : $entry->type->intent()">{{ $entry->isVoided() ? 'ابطال' : $entry->type->label() }}</x-badge></div>
                    <div class="mt-3 flex items-end justify-between"><span class="text-xs text-gray-500">مبلغ</span><strong class="text-base {{ $entry->type === \App\Enums\LedgerEntryType::Credit && !$entry->isVoided() ? 'text-emerald-700' : '' }}">{{ number_format($entry->amount) }} تومان</strong></div>
                    @if($entry->reference)<div class="mt-2 text-xs text-gray-500">مرجع: {{ $entry->reference }}</div>@endif
                    @if(!$entry->isVoided() && auth()->user()->can('finance.void_entry'))<button type="button" class="mt-3 text-xs font-semibold text-rose-600" x-data @click="$dispatch('open-void-entry', { id: {{ $entry->id }}, description: @js($entry->description) })">ابطال سند</button>@endif
                </article>
            @empty
                <div class="p-8 text-center text-sm text-gray-500">هنوز سند مالی ثبت نشده است.</div>
            @endforelse
        </div>
        @if($entries->hasPages())<div class="border-t border-gray-100 p-4">{{ $entries->links() }}</div>@endif
    </section>

    <section class="panel mt-4 overflow-hidden">
        <div class="flex items-center justify-between border-b border-gray-100 px-4 py-4 sm:px-5"><h2 class="font-bold">آخرین فیش‌های کارت به کارت</h2>@can('finance.review_payments')<a href="{{ route('finance.receipts.index', ['status' => 'all', 'q' => $customer->name]) }}" class="text-sm font-semibold text-emerald-700">فیش‌ها</a>@endcan</div>
        <div class="divide-y divide-gray-100">
            @forelse($receipts as $receipt)
                <a href="{{ route('finance.receipts.show', $receipt) }}" class="flex flex-col gap-3 p-4 hover:bg-gray-50 sm:flex-row sm:items-center sm:justify-between sm:px-5">
                    <div><strong>{{ number_format($receipt->amount) }} تومان</strong><span class="mt-1 block text-xs text-gray-500">{{ app(\App\Support\DatePresenter::class)->date($receipt->paid_at) }} · {{ $receipt->bankAccount?->bank_name ?: 'حساب بانکی حذف‌شده' }}</span></div>
                    <div class="flex items-center justify-between gap-3 sm:justify-end"><x-badge :type="$receipt->status->intent()">{{ $receipt->status->label() }}</x-badge>@if($receipt->ledgerEntry?->isVoided())<span class="text-xs font-semibold text-rose-600">سند مرتبط ابطال شده</span>@endif</div>
                </a>
            @empty
                <div class="p-8 text-center text-sm text-gray-500">فیشی برای این مشتری ثبت نشده است.</div>
            @endforelse
        </div>
    </section>

    @can('finance.create_entry')
        <div x-data="{ open: {{ $errors->hasAny(['type','amount','description','reference','entry_date']) ? 'true' : 'false' }} }" @open-finance-entry.window="open=true" x-cloak x-show="open" class="fixed inset-0 z-50 flex items-end justify-center sm:items-center">
            <button type="button" class="absolute inset-0 bg-black/35" @click="open=false" aria-label="بستن"></button>
            <section class="relative max-h-[90vh] w-full overflow-y-auto rounded-t-lg bg-white p-5 sm:max-w-xl sm:rounded-lg sm:p-6">
                <div class="mb-5 flex items-center justify-between"><div><h2 class="text-lg font-bold">ثبت سند مالی</h2><p class="mt-1 text-xs text-gray-500">مبالغ بر حسب تومان ثبت می‌شوند.</p></div><button type="button" class="grid h-10 w-10 place-items-center rounded-lg bg-gray-50 text-xl" @click="open=false">×</button></div>
                <form method="POST" action="{{ route('finance.customers.entries.store', $customer) }}" class="space-y-4">@csrf
                    <div class="grid gap-4 sm:grid-cols-2"><div><label class="form-label">نوع سند</label><select name="type" class="form-control" required>@foreach($entryTypes as $type)<option value="{{ $type->value }}" @selected(old('type') === $type->value)>{{ $type->label() }}</option>@endforeach</select>@error('type')<p class="form-error">{{ $message }}</p>@enderror</div><div><label class="form-label">مبلغ (تومان)</label><input name="amount" value="{{ old('amount') }}" class="form-control" inputmode="numeric" placeholder="مثلاً 15000000" required>@error('amount')<p class="form-error">{{ $message }}</p>@enderror</div></div>
                    <x-persian-date-input label="تاریخ سند" name="entry_date" :value="old('entry_date', now()->format('Y-m-d'))" required />
                    <div><label class="form-label">شرح سند</label><textarea name="description" rows="3" class="form-control" required>{{ old('description') }}</textarea>@error('description')<p class="form-error">{{ $message }}</p>@enderror</div>
                    <div><label class="form-label">شماره مرجع / توضیح کوتاه</label><input name="reference" value="{{ old('reference') }}" class="form-control" placeholder="اختیاری">@error('reference')<p class="form-error">{{ $message }}</p>@enderror</div>
                    <button class="btn btn-primary w-full">ثبت سند</button>
                </form>
            </section>
        </div>
    @endcan

    @can('finance.void_entry')
        <div x-data="{ open:false, id:null, description:'' }" @open-void-entry.window="open=true; id=$event.detail.id; description=$event.detail.description" x-cloak x-show="open" class="fixed inset-0 z-50 flex items-end justify-center sm:items-center">
            <button type="button" class="absolute inset-0 bg-black/35" @click="open=false"></button>
            <section class="relative w-full rounded-t-lg bg-white p-5 sm:max-w-md sm:rounded-lg sm:p-6">
                <h2 class="text-lg font-bold">ابطال سند مالی</h2><p class="mt-2 text-sm text-gray-500">سند حذف نمی‌شود و سابقه ابطال برای حسابرسی باقی می‌ماند.</p><p class="mt-3 rounded-lg bg-gray-50 p-3 text-sm" x-text="description"></p>
                <form method="POST" :action="'{{ url('/finance/entries') }}/'+id+'/void'" class="mt-4 space-y-4">@csrf @method('PATCH')<div><label class="form-label">دلیل ابطال</label><textarea name="void_reason" rows="3" class="form-control" required></textarea></div><div class="flex gap-2"><button class="btn btn-danger flex-1">تأیید ابطال</button><button type="button" class="btn btn-secondary flex-1" @click="open=false">انصراف</button></div></form>
            </section>
        </div>
    @endcan
</x-layouts.app>
