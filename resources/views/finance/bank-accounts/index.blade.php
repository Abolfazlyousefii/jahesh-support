<x-layouts.app title="حساب‌های بانکی">
    <x-page-header title="حساب‌های بانکی" description="حساب‌هایی که مشتری برای کارت به کارت در پنل خود مشاهده می‌کند"><x-slot:actions><a href="{{ route('finance.index') }}" class="btn btn-secondary">بازگشت</a></x-slot:actions></x-page-header>
    <div class="grid gap-4 lg:grid-cols-[380px_minmax(0,1fr)]">
        <section class="panel p-5 lg:sticky lg:top-20 lg:self-start"><h2 class="font-bold">افزودن حساب</h2><form method="POST" action="{{ route('finance.bank-accounts.store') }}" class="mt-4 space-y-4">@csrf
            <x-input label="نام بانک" name="bank_name" required/><x-input label="نام صاحب حساب" name="account_holder" required/><x-input label="شماره کارت" name="card_number" inputmode="numeric" placeholder="16 رقم"/><x-input label="شماره شبا" name="iban" dir="ltr" placeholder="IR..."/><x-input label="شماره حساب" name="account_number" dir="ltr"/>
            <div class="grid grid-cols-2 gap-3"><x-input label="ترتیب" name="sort_order" type="number" value="0"/><label class="flex items-end gap-2 pb-3 text-sm"><input type="checkbox" name="is_active" value="1" checked class="h-4 w-4"> فعال</label></div><button class="btn btn-primary w-full">ثبت حساب</button>
        </form></section>
        <div class="space-y-3">
            @forelse($accounts as $account)
                <section class="panel p-4 sm:p-5" x-data="{ edit:false }">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between"><div><div class="flex items-center gap-2"><strong>{{ $account->bank_name }}</strong><x-badge :type="$account->is_active ? 'success' : 'neutral'">{{ $account->is_active ? 'فعال' : 'غیرفعال' }}</x-badge></div><span class="mt-1 block text-xs text-gray-500">به نام {{ $account->account_holder }}</span>@if($account->card_number)<span dir="ltr" class="mt-3 block font-mono text-base">{{ $account->maskedCardNumber() }}</span>@endif @if($account->iban)<span dir="ltr" class="mt-1 block break-all text-xs text-gray-500">{{ $account->iban }}</span>@endif</div><div class="flex gap-2"><button type="button" class="btn btn-secondary" @click="edit=!edit">ویرایش</button><form method="POST" action="{{ route('finance.bank-accounts.destroy', $account) }}" data-confirm="این حساب از لیست مشتریان حذف شود؟">@csrf @method('DELETE')<button class="btn btn-danger">حذف</button></form></div></div>
                    <form x-cloak x-show="edit" method="POST" action="{{ route('finance.bank-accounts.update', $account) }}" class="mt-5 grid gap-3 border-t border-gray-100 pt-5 sm:grid-cols-2">@csrf @method('PUT')
                        <x-input label="نام بانک" name="bank_name" :value="$account->bank_name" required/><x-input label="نام صاحب حساب" name="account_holder" :value="$account->account_holder" required/><x-input label="شماره کارت" name="card_number" :value="$account->card_number"/><x-input label="شبا" name="iban" :value="$account->iban" dir="ltr"/><x-input label="شماره حساب" name="account_number" :value="$account->account_number" dir="ltr"/><x-input label="ترتیب" name="sort_order" type="number" :value="$account->sort_order"/><label class="flex items-center gap-2 text-sm sm:col-span-2"><input type="checkbox" name="is_active" value="1" @checked($account->is_active)> فعال باشد</label><button class="btn btn-primary sm:col-span-2">ذخیره تغییرات</button>
                    </form>
                </section>
            @empty<div class="panel p-10 text-center text-sm text-gray-500">هنوز حساب بانکی ثبت نشده است.</div>@endforelse
        </div>
    </div>
</x-layouts.app>
