<x-layouts.portal title="درخواست جدید">
    <section class="portal-page-head portal-page-head-compact">
        <div>
            <a href="{{ route('portal.tickets.index') }}" class="portal-back-link">بازگشت به درخواست‌ها</a>
            <h1>درخواست پشتیبانی جدید</h1>
            <p>موضوع را کوتاه و توضیحات را تا حد امکان کامل بنویسید تا بررسی سریع‌تر انجام شود.</p>
        </div>
    </section>

    <div class="portal-form-grid">
        <form method="POST" action="{{ route('portal.tickets.store') }}" class="portal-card portal-form-card">
            @csrf
            <div class="portal-form-section">
                <x-input label="موضوع درخواست" name="subject" required autofocus placeholder="مثلاً مشکل ورود به سایت" />

                <div>
                    <label for="priority" class="form-label">اولویت</label>
                    <select id="priority" name="priority" class="form-control">
                        @foreach($priorities as $priority)
                            <option value="{{ $priority->value }}" @selected(old('priority', 'normal') === $priority->value)>{{ $priority->label() }}</option>
                        @endforeach
                    </select>
                    @error('priority')<p class="form-error">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="description" class="form-label">توضیحات</label>
                    <textarea id="description" name="description" rows="8" class="form-control" required placeholder="شرح دقیق درخواست، خطا یا تغییری که نیاز دارید...">{{ old('description') }}</textarea>
                    @error('description')<p class="form-error">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="portal-form-actions">
                <a href="{{ route('portal.tickets.index') }}" class="btn btn-secondary">انصراف</a>
                <x-button>ارسال درخواست</x-button>
            </div>
        </form>

        <aside class="portal-card portal-guide-card">
            <span class="portal-stat-icon"><x-icon name="tickets" /></span>
            <h2>برای بررسی سریع‌تر</h2>
            <ul>
                <li>موضوع درخواست را واضح و کوتاه بنویسید.</li>
                <li>اگر خطایی مشاهده می‌کنید، متن دقیق خطا را ذکر کنید.</li>
                <li>اولویت فوری را فقط برای موارد واقعاً ضروری انتخاب کنید.</li>
            </ul>
            <p>بعد از ثبت درخواست، ادامه گفتگو از همان صفحه انجام می‌شود.</p>
        </aside>
    </div>
</x-layouts.portal>
