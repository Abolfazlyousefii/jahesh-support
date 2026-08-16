<div class="grid gap-4 sm:grid-cols-2">
    <x-input label="نام و نام خانوادگی" name="name" :value="$user?->name" required />
    <x-input label="شماره موبایل" name="phone" :value="$user?->phone" inputmode="numeric" dir="ltr" required />
    <x-input label="{{ $user ? 'رمز جدید (اختیاری)' : 'رمز عبور' }}" name="password" type="password" :required="!$user" autocomplete="new-password" />
    <x-input label="تکرار رمز عبور" name="password_confirmation" type="password" :required="!$user" autocomplete="new-password" />
</div>
@can('team.assign_roles')
    <fieldset class="mt-5"><legend class="form-label">نقش‌ها</legend><div class="grid gap-2 sm:grid-cols-2">@foreach($roles as $role)<label class="flex min-h-11 items-center gap-2 rounded-lg border border-gray-200 px-3"><input type="checkbox" name="role_ids[]" value="{{ $role->id }}" class="accent-emerald-500" @checked(in_array($role->id, old('role_ids', $user?->roles->pluck('id')->all() ?? [])))><span>{{ $role->title }}</span></label>@endforeach</div>@error('role_ids.*')<p class="form-error">{{ $message }}</p>@enderror</fieldset>
@endcan
<label class="mt-5 flex min-h-11 items-center gap-2"><input type="checkbox" name="is_active" value="1" class="accent-emerald-500" @checked(old('is_active', $user?->is_active ?? true))><span>حساب فعال باشد</span></label>
<div class="mt-6 flex flex-col-reverse gap-2 sm:flex-row"><a href="{{ route('team.index') }}" class="btn btn-secondary">انصراف</a><x-button>ذخیره</x-button></div>
