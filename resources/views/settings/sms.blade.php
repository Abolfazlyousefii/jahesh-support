<x-layouts.app title="تنظیمات پیامک">
    <x-page-header title="تنظیمات پیامک" description="اتصال ملی پیامک، مدیریت پترن‌ها و مشاهده گزارش ارسال‌ها" />

    @if($errors->has('sms_connection') || $errors->has('sms_test'))
        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            {{ $errors->first('sms_connection') ?: $errors->first('sms_test') }}
        </div>
    @endif

    <form method="POST" action="{{ route('settings.sms.update') }}" class="space-y-5">
        @csrf
        @method('PUT')

        <section class="panel overflow-hidden">
            <div class="border-b border-gray-100 px-4 py-4 sm:px-5">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 class="font-bold">اتصال ملی پیامک</h2>
                        <p class="mt-1 text-xs leading-6 text-gray-500">اطلاعات وب‌سرویس را داخل نرم‌افزار نگهداری کنید؛ رمز به‌صورت رمزنگاری‌شده ذخیره می‌شود.</p>
                    </div>
                    <label class="flex items-center gap-2 text-sm font-medium">
                        <input type="hidden" name="enabled" value="0">
                        <input type="checkbox" name="enabled" value="1" @checked(old('enabled', $setting->enabled)) class="h-4 w-4 rounded border-gray-300">
                        فعال‌سازی ارسال پیامک
                    </label>
                </div>
            </div>

            <div class="grid gap-4 p-4 sm:grid-cols-2 sm:p-5">
                <div>
                    <label class="form-label">نام کاربری وب‌سرویس</label>
                    <input class="form-control" name="webservice_username" value="{{ old('webservice_username', $setting->webservice_username) }}" autocomplete="off">
                    @error('webservice_username')<div class="form-error">{{ $message }}</div>@enderror
                </div>

                <div>
                    <label class="form-label">رمز وب‌سرویس</label>
                    <input class="form-control" type="password" name="webservice_password" autocomplete="new-password" placeholder="{{ $setting->webservice_password ? 'برای حفظ رمز فعلی خالی بگذارید' : 'رمز وب‌سرویس' }}">
                    @if($setting->webservice_password)
                        <p class="mt-1.5 text-xs text-emerald-700">رمز قبلاً ثبت شده است.</p>
                    @endif
                    @error('webservice_password')<div class="form-error">{{ $message }}</div>@enderror
                </div>
            </div>

            <div class="flex flex-col gap-2 border-t border-gray-100 bg-gray-50/60 px-4 py-3 sm:flex-row sm:items-center sm:justify-between sm:px-5">
                <p class="text-xs text-gray-500">Provider: ملی پیامک / REST Pattern API</p>
                <button type="submit" form="sms-test-connection-form" class="btn btn-secondary w-full sm:w-auto">
                    تست اتصال
                </button>
            </div>
        </section>

        <section class="panel overflow-hidden">
            <div class="border-b border-gray-100 px-4 py-4 sm:px-5">
                <h2 class="font-bold">گیرندگان داخلی اعلان‌ها</h2>
                <p class="mt-1 text-xs leading-6 text-gray-500">برای تیکت جدید و فیش پرداخت جدید، اعضایی که باید پیامک دریافت کنند انتخاب کنید.</p>
            </div>
            <div class="grid gap-2 p-4 sm:grid-cols-2 lg:grid-cols-3 sm:p-5">
                @php($selectedUsers = collect(old('internal_recipient_user_ids', $setting->internal_recipient_user_ids ?? []))->map(fn($id) => (int) $id))
                @forelse($users as $user)
                    <label class="flex min-h-12 items-center gap-3 rounded-lg border border-gray-200 bg-white px-3 py-2">
                        <input type="checkbox" name="internal_recipient_user_ids[]" value="{{ $user->id }}" @checked($selectedUsers->contains($user->id)) class="h-4 w-4 rounded border-gray-300">
                        <span class="min-w-0">
                            <strong class="block truncate text-sm">{{ $user->name }}</strong>
                            <small class="text-gray-500">{{ $user->phone }}</small>
                        </span>
                    </label>
                @empty
                    <p class="text-sm text-gray-500">عضو فعال برای انتخاب وجود ندارد.</p>
                @endforelse
            </div>
        </section>

        <section class="panel overflow-hidden">
            <div class="border-b border-gray-100 px-4 py-4 sm:px-5">
                <h2 class="font-bold">پترن‌های پیامک</h2>
                <p class="mt-1 text-xs leading-6 text-gray-500">Body ID هر پترن را بعد از تأیید در پنل ملی پیامک وارد کنید. ترتیب متغیرها باید مطابق راهنمای زیر باشد.</p>
            </div>

            <div class="divide-y divide-gray-100">
                @foreach($patterns as $pattern)
                    @php($definition = $definitions[$pattern->key])
                    <div class="grid gap-4 p-4 lg:grid-cols-[minmax(0,1fr)_180px_120px] lg:items-center sm:p-5">
                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <strong class="text-sm">{{ $pattern->title }}</strong>
                                <code class="rounded bg-gray-100 px-2 py-1 text-[11px] text-gray-600">{{ $pattern->key }}</code>
                            </div>
                            <p class="mt-2 text-xs leading-6 text-gray-500">{{ $definition['template'] }}</p>
                            <p class="mt-1 text-[11px] leading-6 text-gray-400">
                                ترتیب متغیرها:
                                {{ implode(' ← ', $definition['parameters']) }}
                            </p>
                        </div>

                        <div>
                            <label class="form-label">Body ID</label>
                            <input class="form-control" inputmode="numeric" name="patterns[{{ $pattern->key }}][body_id]" value="{{ old("patterns.{$pattern->key}.body_id", $pattern->body_id) }}" placeholder="مثلاً 12345">
                        </div>

                        <label class="flex items-center gap-2 lg:justify-end">
                            <input type="hidden" name="patterns[{{ $pattern->key }}][enabled]" value="0">
                            <input type="checkbox" name="patterns[{{ $pattern->key }}][enabled]" value="1" @checked(old("patterns.{$pattern->key}.enabled", $pattern->enabled)) class="h-4 w-4 rounded border-gray-300">
                            <span class="text-sm">فعال</span>
                        </label>
                    </div>
                @endforeach
            </div>
        </section>

        <div class="flex justify-end">
            <button class="btn btn-primary w-full sm:w-auto">ذخیره تنظیمات پیامک</button>
        </div>
    </form>

    <form id="sms-test-connection-form" method="POST" action="{{ route('settings.sms.test-connection') }}" class="hidden">
        @csrf
    </form>

    <section class="panel mt-6 overflow-hidden">
        <div class="border-b border-gray-100 px-4 py-4 sm:px-5">
            <h2 class="font-bold">ارسال پیامک تست</h2>
            <p class="mt-1 text-xs leading-6 text-gray-500">پس از ذخیره اطلاعات اتصال و Body ID، یک پترن را روی شماره دلخواه تست کنید.</p>
        </div>
        <form method="POST" action="{{ route('settings.sms.test-pattern') }}" class="grid gap-3 p-4 sm:grid-cols-[1fr_1fr_auto] sm:items-end sm:p-5">
            @csrf
            <div>
                <label class="form-label">شماره موبایل</label>
                <input class="form-control" name="phone" value="{{ old('phone') }}" placeholder="09xxxxxxxxx" inputmode="tel">
            </div>
            <div>
                <label class="form-label">پترن</label>
                <select class="form-control" name="pattern_key">
                    @foreach($patterns as $pattern)
                        <option value="{{ $pattern->key }}">{{ $pattern->title }}</option>
                    @endforeach
                </select>
            </div>
            <button class="btn btn-secondary">ارسال تست</button>
        </form>
    </section>

    <section class="panel mt-6 overflow-hidden">
        <div class="flex flex-col gap-3 border-b border-gray-100 px-4 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-5">
            <div>
                <h2 class="font-bold">گزارش پیامک‌ها</h2>
                <p class="mt-1 text-xs text-gray-500">متن پیام و کد OTP در گزارش ذخیره نمی‌شود.</p>
            </div>
            <div class="flex flex-wrap gap-2 text-xs">
                @foreach(['' => 'همه', 'sent' => 'موفق', 'failed' => 'ناموفق', 'queued' => 'در صف', 'skipped' => 'ردشده'] as $value => $label)
                    <a href="{{ route('settings.sms.index', array_filter(['status' => $value])) }}" class="rounded-lg border px-3 py-2 {{ ($status ?? '') === $value ? 'border-emerald-300 bg-emerald-50 text-emerald-800' : 'border-gray-200 bg-white text-gray-600' }}">{{ $label }}</a>
                @endforeach
            </div>
        </div>

        <div class="hidden overflow-x-auto md:block">
            <table class="w-full text-right text-sm">
                <thead class="bg-gray-50 text-xs text-gray-500">
                    <tr>
                        <th class="px-4 py-3 font-medium">زمان</th>
                        <th class="px-4 py-3 font-medium">گیرنده</th>
                        <th class="px-4 py-3 font-medium">پترن</th>
                        <th class="px-4 py-3 font-medium">وضعیت</th>
                        <th class="px-4 py-3 font-medium">شناسه ملی پیامک</th>
                        <th class="px-4 py-3 font-medium">خطا</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($logs as $log)
                        <tr>
                            <td class="px-4 py-3 text-xs text-gray-500">{{ $log->created_at?->format('Y-m-d H:i') }}</td>
                            <td class="px-4 py-3">{{ $log->recipient }}</td>
                            <td class="px-4 py-3"><code class="text-xs">{{ $log->pattern_key }}</code></td>
                            <td class="px-4 py-3">
                                @php($statusClass = match($log->status) { 'sent' => 'bg-emerald-50 text-emerald-700', 'failed' => 'bg-red-50 text-red-700', 'queued' => 'bg-blue-50 text-blue-700', default => 'bg-gray-100 text-gray-600' })
                                <span class="rounded-full px-2 py-1 text-xs {{ $statusClass }}">{{ $log->status }}</span>
                            </td>
                            <td class="px-4 py-3 text-xs">{{ $log->provider_message_id ?: '—' }}</td>
                            <td class="max-w-xs px-4 py-3 text-xs text-red-600">{{ $log->error ?: '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-10 text-center text-sm text-gray-500">هنوز گزارشی ثبت نشده است.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="divide-y divide-gray-100 md:hidden">
            @forelse($logs as $log)
                <div class="space-y-2 p-4">
                    <div class="flex items-center justify-between gap-3">
                        <strong class="text-sm">{{ $log->recipient }}</strong>
                        <span class="text-xs text-gray-500">{{ $log->status }}</span>
                    </div>
                    <code class="block text-xs text-gray-500">{{ $log->pattern_key }}</code>
                    @if($log->error)<p class="text-xs leading-6 text-red-600">{{ $log->error }}</p>@endif
                </div>
            @empty
                <p class="p-8 text-center text-sm text-gray-500">هنوز گزارشی ثبت نشده است.</p>
            @endforelse
        </div>

        @if($logs->hasPages())
            <div class="border-t border-gray-100 p-4">{{ $logs->links() }}</div>
        @endif
    </section>
</x-layouts.app>
