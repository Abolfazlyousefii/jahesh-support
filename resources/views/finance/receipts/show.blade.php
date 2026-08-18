<x-layouts.app :title="'فیش پرداخت #'.$receipt->id">
    <x-page-header :title="'فیش پرداخت #'.$receipt->id" :description="$receipt->customer->name">
        <x-slot:actions><a href="{{ route('finance.receipts.index') }}" class="btn btn-secondary">لیست فیش‌ها</a></x-slot:actions>
    </x-page-header>

    <div class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_360px]">
        <section class="panel overflow-hidden">
            <div class="border-b border-gray-100 p-4 sm:p-5"><div class="flex items-center justify-between"><h2 class="font-bold">تصویر فیش</h2><a href="{{ route('finance.receipts.file', $receipt) }}" target="_blank" class="text-sm font-semibold text-emerald-700">باز کردن فایل</a></div></div>
            <div class="grid min-h-[360px] place-items-center bg-gray-50 p-4">
                @if(str_starts_with((string)$receipt->mime_type, 'image/'))
                    <img src="{{ route('finance.receipts.file', $receipt) }}" alt="فیش پرداخت" class="max-h-[680px] w-auto max-w-full rounded-lg border border-gray-200 bg-white object-contain">
                @else
                    <div class="text-center"><x-icon name="finance" class="mx-auto h-12 w-12 text-gray-400"/><p class="mt-3 text-sm text-gray-500">فایل فیش PDF است.</p><a href="{{ route('finance.receipts.file', $receipt) }}" target="_blank" class="btn btn-primary mt-4">مشاهده PDF</a></div>
                @endif
            </div>
        </section>

        <aside class="space-y-4">
            <section class="panel p-5">
                <div class="flex items-center justify-between"><h2 class="font-bold">جزئیات پرداخت</h2><x-badge :type="$receipt->status->intent()">{{ $receipt->status->label() }}</x-badge></div>
                <dl class="mt-5 space-y-4 text-sm">
                    <div><dt class="text-xs text-gray-500">مشتری</dt><dd class="mt-1 font-semibold"><a class="text-emerald-700" href="{{ route('finance.customers.show', $receipt->customer) }}">{{ $receipt->customer->name }}</a></dd></div>
                    <div><dt class="text-xs text-gray-500">مبلغ</dt><dd class="mt-1 text-lg font-bold">{{ number_format($receipt->amount) }} تومان</dd></div>
                    <div><dt class="text-xs text-gray-500">تاریخ پرداخت</dt><dd class="mt-1">{{ app(\App\Support\DatePresenter::class)->date($receipt->paid_at) }}</dd></div>
                    <div><dt class="text-xs text-gray-500">حساب مقصد</dt><dd class="mt-1">{{ $receipt->bankAccount?->bank_name ?: '—' }}@if($receipt->bankAccount?->card_number)<span dir="ltr" class="mt-1 block text-xs text-gray-500">{{ $receipt->bankAccount->maskedCardNumber() }}</span>@endif</dd></div>
                    <div><dt class="text-xs text-gray-500">شماره پیگیری</dt><dd class="mt-1">{{ $receipt->tracking_code ?: '—' }}</dd></div>
                    <div><dt class="text-xs text-gray-500">توضیح مشتری</dt><dd class="mt-1 whitespace-pre-line">{{ $receipt->customer_note ?: '—' }}</dd></div>
                </dl>
            </section>

            @if($receipt->status === \App\Enums\PaymentReceiptStatus::Pending)
                @can('finance.review_payments')
                    <section class="panel p-5">
                        <h2 class="font-bold">بررسی فیش</h2><p class="mt-2 text-xs leading-5 text-gray-500">تأیید فیش به‌صورت تراکنشی یک سند بستانکار برای مشتری ایجاد می‌کند و دوباره قابل تأیید نیست.</p>
                        <form method="POST" action="{{ route('finance.receipts.approve', $receipt) }}" class="mt-4">@csrf @method('PATCH')<button class="btn btn-primary w-full" onclick="return confirm('فیش را تأیید می‌کنید؟ سند بستانکار ثبت خواهد شد.')">تأیید و ثبت بستانکار</button></form>
                        <form method="POST" action="{{ route('finance.receipts.reject', $receipt) }}" class="mt-3 space-y-3">@csrf @method('PATCH')<div><label class="form-label">دلیل رد</label><textarea name="rejection_reason" rows="3" class="form-control" required>{{ old('rejection_reason') }}</textarea>@error('rejection_reason')<p class="form-error">{{ $message }}</p>@enderror</div><button class="btn btn-danger w-full">رد فیش</button></form>
                    </section>
                @endcan
            @else
                <section class="panel p-5 text-sm"><h2 class="font-bold">نتیجه بررسی</h2><p class="mt-3 text-gray-600">بررسی توسط {{ $receipt->reviewer?->name ?: '—' }} در {{ app(\App\Support\DatePresenter::class)->dateTime($receipt->reviewed_at) }}</p>@if($receipt->status === \App\Enums\PaymentReceiptStatus::Rejected)<div class="mt-3 rounded-lg bg-rose-50 p-3 text-rose-700">{{ $receipt->rejection_reason }}</div>@endif @if($receipt->ledgerEntry)<div class="mt-3 rounded-lg bg-emerald-50 p-3 text-emerald-800">سند بستانکار #{{ $receipt->ledgerEntry->id }} ثبت شده است.@if($receipt->ledgerEntry->isVoided()) <strong class="block mt-1 text-rose-700">این سند بعداً ابطال شده است.</strong>@endif</div>@endif</section>
            @endif
        </aside>
    </div>
</x-layouts.app>
