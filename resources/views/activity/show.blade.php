<x-layouts.app title="جزئیات فعالیت">
    <x-page-header title="جزئیات فعالیت" description="{{ \App\Support\ActivityCatalog::eventLabel($activity->event) }}">
        <x-slot:actions>
            <a href="{{ route('activity.index') }}" class="btn btn-secondary">بازگشت به گزارش‌ها</a>
        </x-slot:actions>
    </x-page-header>

    <div class="grid gap-4 xl:grid-cols-[1fr_320px]">
        <section class="panel overflow-hidden">
            <div class="border-b border-gray-100 p-5">
                <div class="flex flex-wrap items-center gap-2">
                    <strong class="text-base">{{ \App\Support\ActivityCatalog::eventLabel($activity->event) }}</strong>
                    <x-badge>{{ \App\Support\ActivityCatalog::eventGroup($activity->event) }}</x-badge>
                </div>
                <p class="mt-2 text-sm leading-7 text-gray-600">{{ $activity->description ?: 'شرح اضافه‌ای برای این فعالیت ثبت نشده است.' }}</p>
            </div>

            @php
                $keys = collect(array_keys($activity->old_values ?? []))
                    ->merge(array_keys($activity->new_values ?? []))
                    ->unique()
                    ->values();
            @endphp

            <div class="p-5">
                <h2 class="mb-3 text-sm font-bold">تغییرات ثبت‌شده</h2>
                @if($keys->isEmpty())
                    <div class="rounded-lg border border-dashed border-gray-200 bg-gray-50 p-5 text-sm text-gray-500">
                        این رویداد تغییر مقدار قابل مقایسه‌ای نداشته است.
                    </div>
                @else
                    <div class="overflow-x-auto rounded-lg border border-gray-100">
                        <table class="w-full min-w-[620px] text-right">
                            <thead class="bg-gray-50 text-xs text-gray-500">
                                <tr>
                                    <th class="p-3">فیلد</th>
                                    <th class="p-3">قبل</th>
                                    <th class="p-3">بعد</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach($keys as $key)
                                    <tr>
                                        <td class="p-3 text-sm font-semibold">{{ \App\Support\ActivityCatalog::fieldLabel($key) }}</td>
                                        <td class="max-w-sm p-3 text-sm text-gray-500">{{ \App\Support\ActivityCatalog::formatValue(data_get($activity->old_values, $key)) }}</td>
                                        <td class="max-w-sm p-3 text-sm text-gray-800">{{ \App\Support\ActivityCatalog::formatValue(data_get($activity->new_values, $key)) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            @if(!empty($activity->metadata))
                <div class="border-t border-gray-100 p-5">
                    <h2 class="mb-3 text-sm font-bold">اطلاعات تکمیلی</h2>
                    <div class="grid gap-2 sm:grid-cols-2">
                        @foreach($activity->metadata as $key => $value)
                            <div class="rounded-lg bg-gray-50 p-3">
                                <span class="block text-xs text-gray-500">{{ \App\Support\ActivityCatalog::fieldLabel((string) $key) }}</span>
                                <strong class="mt-1 block text-sm">{{ \App\Support\ActivityCatalog::formatValue($value) }}</strong>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </section>

        <aside class="space-y-4">
            <section class="panel p-4">
                <h2 class="mb-4 text-sm font-bold">اطلاعات رویداد</h2>
                <dl class="space-y-3 text-sm">
                    <div><dt class="text-xs text-gray-500">انجام‌دهنده</dt><dd class="mt-1 font-semibold">{{ $activity->actor_name ?: 'سیستم' }}</dd></div>
                    <div><dt class="text-xs text-gray-500">مورد مرتبط</dt><dd class="mt-1 font-semibold">{{ $activity->subject_label ?: '—' }}</dd></div>
                    <div><dt class="text-xs text-gray-500">بخش</dt><dd class="mt-1">{{ \App\Support\ActivityCatalog::subjectTypeLabel($activity->subject_type) }}</dd></div>
                    <div><dt class="text-xs text-gray-500">زمان</dt><dd class="mt-1">{{ app(\App\Support\DatePresenter::class)->dateTime($activity->created_at) }}</dd></div>
                </dl>
            </section>

            <section class="panel p-4">
                <h2 class="mb-4 text-sm font-bold">اطلاعات فنی</h2>
                <dl class="space-y-3 text-xs">
                    <div><dt class="text-gray-500">IP</dt><dd class="mt-1 break-all" dir="ltr">{{ $activity->ip_address ?: '—' }}</dd></div>
                    <div><dt class="text-gray-500">شناسه رویداد</dt><dd class="mt-1" dir="ltr">#{{ $activity->id }}</dd></div>
                    <div><dt class="text-gray-500">User Agent</dt><dd class="mt-1 break-words text-gray-600" dir="ltr">{{ $activity->user_agent ?: '—' }}</dd></div>
                </dl>
            </section>
        </aside>
    </div>
</x-layouts.app>
