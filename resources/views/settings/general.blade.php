<x-layouts.app title="تنظیمات عمومی">
    <x-page-header title="تنظیمات" description="مدیریت اطلاعات پایه نرم‌افزار و پنل مشتریان" />

    @include('settings.partials.tabs')

    <form method="POST" action="{{ route('settings.general.update') }}" class="space-y-5">
        @csrf
        @method('PUT')

        <section class="panel overflow-hidden">
            <div class="border-b border-gray-100 px-4 py-4 sm:px-5">
                <h2 class="font-bold">اطلاعات مجموعه</h2>
                <p class="mt-1 text-xs leading-6 text-gray-500">اطلاعاتی که در عنوان نرم‌افزار، پنل مدیریت و بخش‌های ارتباطی استفاده می‌شوند.</p>
            </div>

            <div class="grid gap-4 p-4 sm:grid-cols-2 sm:p-5">
                <div>
                    <label class="form-label">نام مجموعه</label>
                    <input class="form-control" name="company_name" value="{{ old('company_name', $settings['general.company_name']) }}" placeholder="مثلاً تیم جهش">
                    @error('company_name')<div class="form-error">{{ $message }}</div>@enderror
                </div>

                <div>
                    <label class="form-label">عنوان نرم‌افزار</label>
                    <input class="form-control" name="app_name" value="{{ old('app_name', $settings['general.app_name']) }}" placeholder="مثلاً سامانه پشتیبانی جهش">
                    @error('app_name')<div class="form-error">{{ $message }}</div>@enderror
                </div>

                <div>
                    <label class="form-label">شماره پشتیبانی</label>
                    <input class="form-control" name="support_phone" value="{{ old('support_phone', $settings['general.support_phone']) }}" placeholder="مثلاً 09xxxxxxxxx" inputmode="tel">
                    @error('support_phone')<div class="form-error">{{ $message }}</div>@enderror
                </div>

                <div>
                    <label class="form-label">ساعات پاسخگویی</label>
                    <input class="form-control" name="support_hours" value="{{ old('support_hours', $settings['general.support_hours']) }}" placeholder="مثلاً 09:00 تا 23:00">
                    @error('support_hours')<div class="form-error">{{ $message }}</div>@enderror
                </div>

                <div class="sm:col-span-2">
                    <label class="form-label">متن کوتاه پشتیبانی</label>
                    <textarea class="form-control min-h-24" name="support_text" placeholder="متن کوتاهی که در بخش پشتیبانی پنل مشتری نمایش داده می‌شود.">{{ old('support_text', $settings['general.support_text']) }}</textarea>
                    @error('support_text')<div class="form-error">{{ $message }}</div>@enderror
                </div>
            </div>
        </section>

        <section class="panel overflow-hidden">
            <div class="border-b border-gray-100 px-4 py-4 sm:px-5">
                <h2 class="font-bold">پنل مشتریان</h2>
                <p class="mt-1 text-xs leading-6 text-gray-500">متن‌ها و اطلاعاتی که مشتری بعد از ورود به پنل مشاهده می‌کند.</p>
            </div>

            <div class="grid gap-4 p-4 sm:grid-cols-2 sm:p-5">
                <div>
                    <label class="form-label">عنوان پنل مشتری</label>
                    <input class="form-control" name="portal_title" value="{{ old('portal_title', $settings['portal.title']) }}">
                    @error('portal_title')<div class="form-error">{{ $message }}</div>@enderror
                </div>

                <div>
                    <label class="form-label">تعداد تیکت‌های باز در داشبورد</label>
                    <input class="form-control" type="number" min="3" max="20" name="portal_active_ticket_limit" value="{{ old('portal_active_ticket_limit', $settings['portal.active_ticket_limit']) }}">
                    @error('portal_active_ticket_limit')<div class="form-error">{{ $message }}</div>@enderror
                </div>

                <div class="sm:col-span-2">
                    <label class="form-label">متن خوش‌آمدگویی</label>
                    <input class="form-control" name="portal_welcome_text" value="{{ old('portal_welcome_text', $settings['portal.welcome_text']) }}">
                    @error('portal_welcome_text')<div class="form-error">{{ $message }}</div>@enderror
                </div>

                <label class="flex min-h-14 items-center gap-3 rounded-lg border border-gray-200 bg-white px-4 py-3">
                    <input type="hidden" name="portal_show_support_phone" value="0">
                    <input type="checkbox" name="portal_show_support_phone" value="1" @checked(old('portal_show_support_phone', $settings['portal.show_support_phone'])) class="h-4 w-4 rounded border-gray-300">
                    <span>
                        <strong class="block text-sm">نمایش شماره پشتیبانی</strong>
                        <small class="mt-1 block text-xs text-gray-500">شماره ثبت‌شده در کارت پشتیبانی پنل مشتری نمایش داده شود.</small>
                    </span>
                </label>

                <label class="flex min-h-14 items-center gap-3 rounded-lg border border-gray-200 bg-white px-4 py-3">
                    <input type="hidden" name="portal_show_support_hours" value="0">
                    <input type="checkbox" name="portal_show_support_hours" value="1" @checked(old('portal_show_support_hours', $settings['portal.show_support_hours'])) class="h-4 w-4 rounded border-gray-300">
                    <span>
                        <strong class="block text-sm">نمایش ساعات پاسخگویی</strong>
                        <small class="mt-1 block text-xs text-gray-500">ساعات پاسخگویی در کارت پشتیبانی مشتری نمایش داده شود.</small>
                    </span>
                </label>
            </div>
        </section>

        <section class="panel overflow-hidden">
            <div class="border-b border-gray-100 px-4 py-4 sm:px-5">
                <h2 class="font-bold">رفتار عمومی لیست‌ها</h2>
                <p class="mt-1 text-xs leading-6 text-gray-500">تعداد رکوردهای نمایش‌داده‌شده در صفحات لیستی مدیریت و پنل مشتری.</p>
            </div>

            <div class="p-4 sm:p-5">
                <div class="max-w-xs">
                    <label class="form-label">تعداد آیتم در هر صفحه</label>
                    <select class="form-control" name="pagination_per_page">
                        @foreach([10, 15, 20, 25, 30, 50, 100] as $size)
                            <option value="{{ $size }}" @selected((int) old('pagination_per_page', $settings['general.pagination_per_page']) === $size)>{{ $size }}</option>
                        @endforeach
                    </select>
                    @error('pagination_per_page')<div class="form-error">{{ $message }}</div>@enderror
                </div>
            </div>
        </section>

        <div class="flex justify-end">
            <button class="btn btn-primary w-full sm:w-auto">ذخیره تنظیمات عمومی</button>
        </div>
    </form>
</x-layouts.app>
