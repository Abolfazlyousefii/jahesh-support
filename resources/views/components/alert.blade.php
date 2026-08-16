@if(session('success'))
    <div class="mb-5 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-emerald-800" role="status">{{ session('success') }}</div>
@endif
@if($errors->any())
    <div class="mb-5 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-red-800" role="alert">لطفاً خطاهای فرم را بررسی کنید.</div>
@endif
