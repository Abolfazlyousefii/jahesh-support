<x-layouts.app title="ویرایش عضو">
    <x-page-header title="ویرایش عضو تیم" description="در صورت خالی‌گذاشتن رمز جدید، رمز فعلی حفظ می‌شود." />
    <form method="POST" action="{{ route('team.update', $user) }}" class="panel max-w-3xl p-5 sm:p-6">@csrf @method('PUT') @include('team.form')</form>
</x-layouts.app>
