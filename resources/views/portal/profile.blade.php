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

        <aside class="portal-card portal-security-card">
            <span class="portal-stat-icon"><x-icon name="shield" /></span>
            <h2>امنیت حساب</h2>
            <p>ورود به حساب شما با شماره موبایل ثبت‌شده انجام می‌شود.</p>
            <div class="portal-security-status">
                <span>رمز عبور</span>
                <strong>{{ filled($customer->password) ? 'فعال' : 'تعریف نشده' }}</strong>
            </div>
            <div class="portal-soft-note">برای تغییر اطلاعات اصلی حساب یا رمز عبور می‌توانید با تیم پشتیبانی جهش در ارتباط باشید.</div>
        </aside>
    </div>
</x-layouts.portal>
