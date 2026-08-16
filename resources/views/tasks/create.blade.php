<x-layouts.app title="ایجاد تسک">
    <x-page-header title="ایجاد تسک" description="کار، مسئول، اولویت و ددلاین را مشخص کنید." />
    <form method="POST" action="{{ route('tasks.store') }}" class="panel p-4 sm:p-6">
        @csrf
        @include('tasks.form', ['task' => null])
    </form>
</x-layouts.app>
