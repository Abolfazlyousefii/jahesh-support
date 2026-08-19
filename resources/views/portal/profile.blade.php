<x-layouts.portal title="حساب من">
    <section class="portal-page-head">
        <div>
            <span class="portal-eyebrow">پروفایل مشتری</span>
            <h1>حساب من</h1>
            <p>اطلاعاتی که برای حساب شما در سامانه جهش ثبت شده است.</p>
        </div>
    </section>

    <section class="portal-profile-hero">
        <div class="portal-profile-avatar">{{ mb_substr($customer->name, 0, 1) }}</div>
        <div class="portal-profile-name">
            <span>حساب فعال</span>
            <h2>{{ $customer->name }}</h2>
            <p>{{ $customer->company_name ?: 'مشتری جهش' }}</p>
        </div>
    </section>

    <div class="portal-profile-grid">
        <section class="portal-card portal-profile-section">
            <div class="portal-card-head portal-card-head-simple">
                <div><h2>اطلاعات تماس</h2><p>اطلاعات پایه و شماره‌های ثبت‌شده</p></div>
            </div>
            <dl class="portal-profile-list">
                <div><dt>نام</dt><dd>{{ $customer->name }}</dd></div>
                <div><dt>نام مجموعه</dt><dd>{{ $customer->company_name ?: '—' }}</dd></div>
                <div><dt>شهر</dt><dd>{{ $customer->city ?: '—' }}</dd></div>
                <div class="wide"><dt>آدرس</dt><dd>{{ $customer->address ?: '—' }}</dd></div>
                <div class="wide"><dt>شماره‌های ثبت‌شده</dt><dd class="portal-phone-list">@foreach($customer->phones as $phone)<span dir="ltr">{{ $phone->phone }}</span>@endforeach</dd></div>
            </dl>
        </section>

        <aside class="portal-card portal-security-card" x-data="{ showCurrent: false, showPassword: false, showConfirmation: false, password: '', confirmation: '' }">
            <span class="portal-stat-icon"><x-icon name="shield" /></span>
            <h2>امنیت حساب</h2>
            <p>رمز عبور حساب را بدون نیاز به پشتیبانی تغییر دهید.</p>

            <div class="portal-security-status">
                <span>رمز عبور</span>
                <strong>{{ filled($customer->password) ? 'فعال' : 'تعریف نشده' }}</strong>
            </div>

            <form method="POST" action="{{ route('portal.profile.password.update') }}" class="mt-5 space-y-4">
                @csrf
                @method('PUT')

                @if(filled($customer->password))
                    <div>
                        <label for="profile-current-password" class="form-label">رمز عبور فعلی</label>
                        <div class="relative">
                            <input id="profile-current-password" name="current_password" :type="showCurrent ? 'text' : 'password'" class="form-control pl-12" autocomplete="current-password" required>
                            <button type="button" @click="showCurrent = !showCurrent" class="absolute left-3 top-1/2 -translate-y-1/2 text-xs font-bold text-gray-500" x-text="showCurrent ? 'مخفی' : 'نمایش'"></button>
                        </div>
                        @error('current_password')<p class="form-error">{{ $message }}</p>@enderror
                    </div>
                @endif

                <div>
                    <label for="profile-new-password" class="form-label">{{ filled($customer->password) ? 'رمز عبور جدید' : 'تعریف رمز عبور' }}</label>
                    <div class="relative">
                        <input id="profile-new-password" name="password" x-model="password" :type="showPassword ? 'text' : 'password'" class="form-control pl-12" autocomplete="new-password" required>
                        <button type="button" @click="showPassword = !showPassword" class="absolute left-3 top-1/2 -translate-y-1/2 text-xs font-bold text-gray-500" x-text="showPassword ? 'مخفی' : 'نمایش'"></button>
                    </div>
                    @error('password')<p class="form-error">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="profile-new-password-confirmation" class="form-label">تکرار رمز عبور جدید</label>
                    <div class="relative">
                        <input id="profile-new-password-confirmation" name="password_confirmation" x-model="confirmation" :type="showConfirmation ? 'text' : 'password'" class="form-control pl-12" autocomplete="new-password" required>
                        <button type="button" @click="showConfirmation = !showConfirmation" class="absolute left-3 top-1/2 -translate-y-1/2 text-xs font-bold text-gray-500" x-text="showConfirmation ? 'مخفی' : 'نمایش'"></button>
                    </div>
                </div>

                <div class="portal-soft-note">
                    <div class="grid gap-1">
                        <span :class="password.length >= 8 ? 'text-emerald-700' : ''">✓ حداقل ۸ کاراکتر</span>
                        <span :class="/[A-Z]/.test(password) ? 'text-emerald-700' : ''">✓ یک حرف بزرگ انگلیسی</span>
                        <span :class="/[0-9]/.test(password) ? 'text-emerald-700' : ''">✓ حداقل یک عدد</span>
                        <span :class="confirmation.length > 0 && confirmation === password ? 'text-emerald-700' : ''">✓ تکرار رمز یکسان</span>
                    </div>
                </div>

                <button type="submit" class="portal-primary-button w-full justify-center">
                    {{ filled($customer->password) ? 'تغییر رمز عبور' : 'تعریف رمز عبور' }}
                </button>
            </form>
        </aside>
    </div>
</x-layouts.portal>
