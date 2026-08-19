<div class="mb-5 flex flex-wrap gap-2 rounded-xl border border-gray-200 bg-white p-2">
    @can('settings.general.manage')
        <a href="{{ route('settings.general.index') }}"
           class="rounded-lg px-4 py-2 text-sm font-medium transition {{ request()->routeIs('settings.general.*') ? 'bg-emerald-50 text-emerald-800' : 'text-gray-600 hover:bg-gray-50' }}">
            تنظیمات عمومی
        </a>
    @endcan

    @can('settings.sms.manage')
        <a href="{{ route('settings.sms.index') }}"
           class="rounded-lg px-4 py-2 text-sm font-medium transition {{ request()->routeIs('settings.sms.*') ? 'bg-emerald-50 text-emerald-800' : 'text-gray-600 hover:bg-gray-50' }}">
            پیامک
        </a>
    @endcan
</div>
