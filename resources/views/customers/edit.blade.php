<x-layouts.app title="ویرایش مشتری">
    <x-page-header title="ویرایش مشتری" description="اطلاعات و شماره‌های تماس مشتری را به‌روزرسانی کنید." />
    <form method="POST" action="{{ route('customers.update', $customer) }}" class="panel p-4 sm:p-6">
        @csrf
        @method('PUT')
        @include('customers.form')
    </form>
</x-layouts.app>
