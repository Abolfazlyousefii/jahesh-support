<div class="grid gap-4 sm:grid-cols-2">
    <x-input label="عنوان فارسی" name="title" :value="$role?->title" required />
    <x-input label="نام فنی" name="name" :value="$role?->name" dir="ltr" placeholder="project-manager" :disabled="$role?->is_system" required />
</div>
@if($role?->is_system)<input type="hidden" name="name" value="{{ $role->name }}">@endif
<fieldset class="mt-6"><legend class="mb-3 text-base font-bold">دسترسی‌های نقش</legend><div class="space-y-4">@foreach($permissionGroups as $group => $permissions)<section class="rounded-lg border border-gray-200 p-4"><h2 class="mb-3 font-bold">{{ $group }}</h2><div class="grid gap-2 sm:grid-cols-2">@foreach($permissions as $permission)<label class="flex min-h-11 cursor-pointer items-center gap-2"><input type="checkbox" name="permission_ids[]" value="{{ $permission->id }}" class="accent-emerald-500" @checked(in_array($permission->id, old('permission_ids', $role?->permissions->pluck('id')->all() ?? [])))><span>{{ $permission->title }}</span></label>@endforeach</div></section>@endforeach</div></fieldset>
<div class="mt-6 flex flex-col-reverse gap-2 sm:flex-row"><a href="{{ route('roles.index') }}" class="btn btn-secondary">انصراف</a><x-button>ذخیره نقش</x-button></div>
