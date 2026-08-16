<x-layouts.app title="ایجاد عضو">
    <x-page-header title="ایجاد عضو تیم" description="اطلاعات ورود و دسترسی عضو جدید را ثبت کنید." />
    <form method="POST" action="{{ route('team.store') }}" class="panel max-w-3xl p-5 sm:p-6">@csrf @include('team.form', ['user' => null])</form>
</x-layouts.app>
