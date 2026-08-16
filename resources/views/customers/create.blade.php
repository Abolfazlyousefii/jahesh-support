<x-layouts.app title="افزودن مشتری">
    <x-page-header title="افزودن مشتری" description="اطلاعات اصلی و شماره‌های تماس مشتری را ثبت کنید." />
    <form method="POST" action="{{ route('customers.store') }}" class="panel p-4 sm:p-6">
        @csrf
        @include('customers.form', ['customer' => null])
    </form>
</x-layouts.app>
