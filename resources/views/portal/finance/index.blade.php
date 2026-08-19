<x-layouts.portal title="مالی و حساب">
    <section class="portal-page-head">
        <div>
            <span class="portal-eyebrow">حساب مالی</span>
            <h1>مالی و پرداخت‌ها</h1>
            <p>مانده حساب، گردش مالی و فیش‌های کارت‌به‌کارت شما در این بخش قرار دارد.</p>
        </div>
    </section>

    <section class="portal-finance-summary">
        <div class="portal-finance-balance">
            <span class="portal-stat-icon"><x-icon name="finance" /></span>
            <div>
                <small>مانده قطعی حساب</small>
                <strong class="{{ $summary['balance_kind'] === 'debit' ? 'text-rose-700' : ($summary['balance_kind'] === 'credit' ? 'text-emerald-700' : '') }}">{{ number_format($summary['balance_abs']) }} <span>تومان</span></strong>
                <p>{{ $summary['balance_kind'] === 'debit' ? 'بدهکار' : ($summary['balance_kind'] === 'credit' ? 'بستانکار' : 'حساب شما تسویه است') }}</p>
            </div>
        </div>
        <div class="portal-finance-pending">
            <small>پرداخت در انتظار بررسی</small>
            <strong>{{ $summary['pending_receipts'] }} مورد</strong>
            <span>{{ number_format($summary['pending_amount']) }} تومان</span>
        </div>
    </section>

    <section class="portal-card portal-payment-section" id="payment">
        <div class="portal-card-head">
            <div>
                <h2>اعلام پرداخت کارت‌به‌کارت</h2>
                <p>پس از واریز، اطلاعات پرداخت و تصویر فیش را برای بررسی ثبت کنید.</p>
            </div>
            <span class="portal-stat-icon portal-stat-icon-small"><x-icon name="upload" /></span>
        </div>

        @if($bankAccounts->isEmpty())
            <div class="portal-warning-box">در حال حاضر حساب بانکی فعالی ثبت نشده است. برای دریافت اطلاعات پرداخت با پشتیبانی تماس بگیرید.</div>
        @else
            <div class="portal-payment-grid">
                <form method="POST" action="{{ route('portal.finance.receipts.store') }}" enctype="multipart/form-data" class="portal-payment-form">
                    @csrf
                    <div class="portal-form-section">
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label class="form-label">واریز به</label>
                                <select name="bank_account_id" class="form-control" required>
                                    <option value="">انتخاب حساب</option>
                                    @foreach($bankAccounts as $account)
                                        <option value="{{ $account->id }}" @selected((string)old('bank_account_id') === (string)$account->id)>{{ $account->bank_name }} - {{ $account->account_holder }}</option>
                                    @endforeach
                                </select>
                                @error('bank_account_id')<p class="form-error">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="form-label">مبلغ پرداخت (تومان)</label>
                                <input name="amount" value="{{ old('amount') }}" inputmode="numeric" class="form-control" placeholder="مثلاً 5000000" required>
                                @error('amount')<p class="form-error">{{ $message }}</p>@enderror
                            </div>
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <x-persian-date-input label="تاریخ پرداخت" name="paid_at" :value="old('paid_at', now()->format('Y-m-d'))" required/>
                            <div>
                                <label class="form-label">شماره پیگیری</label>
                                <input name="tracking_code" value="{{ old('tracking_code') }}" class="form-control" inputmode="numeric" placeholder="اختیاری">
                                @error('tracking_code')<p class="form-error">{{ $message }}</p>@enderror
                            </div>
                        </div>

                        <div x-data="{ fileName: '', fileSize: '' }">
                            <label class="form-label">تصویر فیش</label>
                            <label for="portal-receipt" class="portal-upload-box">
                                <span class="portal-upload-icon"><x-icon name="upload" /></span>
                                <strong x-text="fileName || 'تصویر فیش را انتخاب کنید'"></strong>
                                <small x-text="fileSize || 'JPG، PNG یا PDF تا ۵ مگابایت'"></small>
                                <span class="portal-upload-button" x-text="fileName ? 'تغییر فایل' : 'انتخاب فایل'"></span>
                            </label>
                            <input id="portal-receipt" type="file" name="receipt" accept="image/jpeg,image/png,application/pdf" class="sr-only" required @change="const file=$event.target.files[0]; fileName=file ? file.name : ''; fileSize=file ? (file.size / 1024 / 1024).toFixed(1) + ' MB' : ''">
                            @error('receipt')<p class="form-error">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label class="form-label">توضیحات</label>
                            <textarea name="customer_note" rows="3" class="form-control" placeholder="در صورت نیاز توضیحی درباره پرداخت بنویسید">{{ old('customer_note') }}</textarea>
                            @error('customer_note')<p class="form-error">{{ $message }}</p>@enderror
                        </div>
                    </div>
                    <button class="btn btn-primary portal-submit-payment">ثبت فیش برای بررسی</button>
                </form>

                <aside class="portal-bank-column">
                    <div class="portal-bank-title">
                        <strong>حساب‌های بانکی {{ $generalSettings['general.company_name'] ?? 'تیم جهش' }}</strong>
                        <span>اطلاعات مقصد پرداخت</span>
                    </div>
                    @foreach($bankAccounts as $account)
                        <div class="portal-bank-card" x-data="{ copied:false }">
                            <div class="portal-bank-card-head">
                                <span class="portal-stat-icon portal-stat-icon-small"><x-icon name="bank" /></span>
                                <div><strong>{{ $account->bank_name }}</strong><span>به نام {{ $account->account_holder }}</span></div>
                            </div>
                            @if($account->card_number)
                                <div class="portal-bank-number">
                                    <span dir="ltr">{{ $account->maskedCardNumber() }}</span>
                                    <button type="button" @click="navigator.clipboard.writeText(@js($account->card_number)); copied=true; setTimeout(()=>copied=false,1500)" x-text="copied ? 'کپی شد' : 'کپی'"></button>
                                </div>
                            @endif
                            @if($account->iban)<span dir="ltr" class="portal-iban">{{ $account->iban }}</span>@endif
                        </div>
                    @endforeach
                    <div class="portal-soft-note">فیش ثبت‌شده تا قبل از تأیید واحد مالی، مانده قطعی حساب شما را تغییر نمی‌دهد.</div>
                </aside>
            </div>
        @endif
    </section>

    <section class="portal-card overflow-hidden">
        <div class="portal-card-head">
            <div><h2>گردش حساب</h2><p>اسناد قطعی حساب شما</p></div>
        </div>

        <div class="portal-ledger-desktop">
            <div class="portal-ledger-head"><span>تاریخ</span><span>شرح</span><span>نوع</span><span>مبلغ</span></div>
            @forelse($entries as $entry)
                <div class="portal-ledger-row">
                    <span>{{ app(\App\Support\DatePresenter::class)->date($entry->entry_date) }}</span>
                    <div><strong>{{ $entry->description }}</strong>@if($entry->reference)<small>مرجع {{ $entry->reference }}</small>@endif</div>
                    <span><x-badge :type="$entry->type->intent()">{{ $entry->type->label() }}</x-badge></span>
                    <strong class="{{ $entry->type === \App\Enums\LedgerEntryType::Credit ? 'text-emerald-700' : '' }}">{{ number_format($entry->amount) }} تومان</strong>
                </div>
            @empty
                <div class="portal-empty-row">هنوز گردش مالی قطعی ندارید.</div>
            @endforelse
        </div>

        <div class="portal-ledger-mobile">
            @forelse($entries as $entry)
                <article>
                    <div><strong>{{ $entry->description }}</strong><x-badge :type="$entry->type->intent()">{{ $entry->type->label() }}</x-badge></div>
                    <span>{{ app(\App\Support\DatePresenter::class)->date($entry->entry_date) }}</span>
                    <b>{{ number_format($entry->amount) }} تومان</b>
                </article>
            @empty
                <div class="portal-empty-row">هنوز گردش مالی قطعی ندارید.</div>
            @endforelse
        </div>

        @if($entries->hasPages())<div class="portal-pagination">{{ $entries->links() }}</div>@endif
    </section>

    <section class="portal-card mt-4 overflow-hidden" id="receipts">
        <div class="portal-card-head">
            <div><h2>فیش‌های ارسالی من</h2><p>آخرین پرداخت‌هایی که برای بررسی ثبت کرده‌اید</p></div>
        </div>
        <div class="portal-receipts-list">
            @forelse($receipts as $receipt)
                <article class="portal-receipt-row">
                    <div>
                        <strong>{{ number_format($receipt->amount) }} تومان</strong>
                        <span>{{ app(\App\Support\DatePresenter::class)->date($receipt->paid_at) }} · {{ $receipt->bankAccount?->bank_name ?: 'حساب بانکی' }}</span>
                    </div>
                    <div class="portal-receipt-actions">
                        <x-badge :type="$receipt->status->intent()">{{ $receipt->status->label() }}</x-badge>
                        <a href="{{ route('portal.finance.receipts.file', $receipt) }}" target="_blank">مشاهده فیش</a>
                    </div>
                    @if($receipt->status === \App\Enums\PaymentReceiptStatus::Rejected)
                        <p class="portal-receipt-error">{{ $receipt->rejection_reason }}</p>
                    @elseif($receipt->status === \App\Enums\PaymentReceiptStatus::Approved && $receipt->ledgerEntry?->isVoided())
                        <p class="portal-receipt-error">سند مالی مرتبط ابطال شده است.</p>
                    @endif
                </article>
            @empty
                <div class="portal-empty-row">هنوز فیشی ارسال نکرده‌اید.</div>
            @endforelse
        </div>
    </section>
</x-layouts.portal>
