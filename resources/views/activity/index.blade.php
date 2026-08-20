<x-layouts.app title="گزارش فعالیت‌ها">
    <x-page-header title="گزارش فعالیت‌ها" description="ردیابی عملیات مهم کاربران، مشتریان و رویدادهای مالی و سیستمی." />

    <div class="mb-4 grid gap-3 sm:grid-cols-3">
        <div class="panel p-4">
            <span class="text-xs text-gray-500">فعالیت‌های امروز</span>
            <strong class="mt-2 block text-xl">{{ number_format($todayCount) }}</strong>
        </div>
        <div class="panel p-4">
            <span class="text-xs text-gray-500">۷ روز اخیر</span>
            <strong class="mt-2 block text-xl">{{ number_format($weekCount) }}</strong>
        </div>
        <div class="panel p-4">
            <span class="text-xs text-gray-500">رویدادهای مالی ثبت‌شده</span>
            <strong class="mt-2 block text-xl">{{ number_format($financeCount) }}</strong>
        </div>
    </div>

    <section class="panel relative overflow-visible">
        <form method="GET" class="relative z-20 grid gap-3 border-b border-gray-100 p-4 md:grid-cols-2 xl:grid-cols-12">
            <div class="md:col-span-2 xl:col-span-3">
                <label class="form-label">جستجو</label>
                <input name="q" value="{{ $search }}" class="form-control" placeholder="کاربر، مشتری، عنوان یا شرح عملیات">
            </div>

            <div class="xl:col-span-2">
                <label class="form-label">عملیات</label>
                <select name="event" class="form-control">
                    <option value="">همه عملیات</option>
                    @foreach($events as $key => $definition)
                        <option value="{{ $key }}" @selected($event === $key)>{{ $definition['label'] }}</option>
                    @endforeach
                </select>
            </div>

            <div class="xl:col-span-2">
                <label class="form-label">بخش</label>
                <select name="subject_type" class="form-control">
                    <option value="">همه بخش‌ها</option>
                    @foreach($subjectTypes as $class => $label)
                        <option value="{{ $class }}" @selected($subjectType === $class)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div class="xl:col-span-2">
                <label class="form-label">کاربر تیم</label>
                <select name="actor_id" class="form-control">
                    <option value="">همه کاربران</option>
                    @foreach($users as $user)
                        <option value="{{ $user->id }}" @selected($actorId === $user->id)>{{ $user->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="grid grid-cols-2 gap-3 md:col-span-2 xl:col-span-3">
                <x-persian-date-input label="از تاریخ" name="from" :value="$from" />
                <x-persian-date-input label="تا تاریخ" name="to" :value="$to" />
            </div>

            <div class="flex items-end gap-2 md:col-span-2 xl:col-span-12">
                <button class="btn btn-primary">اعمال فیلتر</button>
                <a href="{{ route('activity.index') }}" class="btn btn-secondary">پاک کردن فیلترها</a>
            </div>
        </form>

        @if($logs->isEmpty())
            <x-empty-state message="فعالیتی مطابق این فیلترها پیدا نشد." />
        @else
            <div class="divide-y divide-gray-100 lg:hidden">
                @foreach($logs as $log)
                    <a href="{{ route('activity.show', $log) }}" class="block p-4 hover:bg-gray-50">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <strong class="block truncate text-sm">{{ \App\Support\ActivityCatalog::eventLabel($log->event) }}</strong>
                                <span class="mt-1 block truncate text-xs text-gray-500">{{ $log->description ?: $log->subject_label }}</span>
                            </div>
                            <x-badge>{{ \App\Support\ActivityCatalog::eventGroup($log->event) }}</x-badge>
                        </div>
                        <div class="mt-3 flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-gray-500">
                            <span>{{ $log->actor_name ?: 'سیستم' }}</span>
                            <span>{{ \App\Support\ActivityCatalog::subjectTypeLabel($log->subject_type) }}</span>
                            <span>{{ app(\App\Support\DatePresenter::class)->dateTime($log->created_at) }}</span>
                        </div>
                    </a>
                @endforeach
            </div>

            <div class="ui-table-wrap hidden lg:block">
                <table class="ui-table min-w-[900px]">
                    <thead class="bg-gray-50 text-xs text-gray-500">
                        <tr>
                            <th class="p-4">زمان</th>
                            <th class="p-4">انجام‌دهنده</th>
                            <th class="p-4">عملیات</th>
                            <th class="p-4">بخش / مورد</th>
                            <th class="p-4">شرح</th>
                            <th class="p-4">جزئیات</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($logs as $log)
                            <tr class="hover:bg-gray-50">
                                <td class="whitespace-nowrap p-4 text-xs text-gray-500">{{ app(\App\Support\DatePresenter::class)->dateTime($log->created_at) }}</td>
                                <td class="p-4">
                                    <strong class="block text-sm">{{ $log->actor_name ?: 'سیستم' }}</strong>
                                    @if($log->ip_address)<span class="mt-1 block text-[11px] text-gray-400" dir="ltr">{{ $log->ip_address }}</span>@endif
                                </td>
                                <td class="p-4">
                                    <strong class="block text-sm">{{ \App\Support\ActivityCatalog::eventLabel($log->event) }}</strong>
                                    <span class="mt-1 block text-xs text-gray-500">{{ \App\Support\ActivityCatalog::eventGroup($log->event) }}</span>
                                </td>
                                <td class="p-4">
                                    <span class="block text-xs text-gray-500">{{ \App\Support\ActivityCatalog::subjectTypeLabel($log->subject_type) }}</span>
                                    <strong class="mt-1 block max-w-56 truncate text-sm">{{ $log->subject_label ?: '—' }}</strong>
                                </td>
                                <td class="max-w-sm p-4 text-sm text-gray-600">{{ $log->description ?: '—' }}</td>
                                <td class="p-4"><a href="{{ route('activity.show', $log) }}" class="btn btn-secondary">مشاهده</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($logs->hasPages())
                <div class="border-t border-gray-100 p-4">{{ $logs->links() }}</div>
            @endif
        @endif
    </section>
</x-layouts.app>
