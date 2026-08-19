اصلاح تست‌های داشبورد جهش

علت 3 Fail فعلی: متن‌های ظاهری Dashboard بعد از مینیمال‌سازی تغییر کرده بودند، اما تست‌ها هنوز به عبارات نسخه قبلی وابسته بودند.

راه‌حل:
- اضافه شدن data-testid و data-count به نقاط مهم Dashboard بدون تغییر ظاهری.
- تست‌ها اکنون به Contract پایدار UI و داده واقعی وابسته‌اند، نه متن‌های تزئینی.
- DashboardController بررسی شد و برای این 3 خطا نیازی به تغییر ندارد.

فایل‌های تغییرکرده:
resources/views/dashboard.blade.php
tests/Feature/DashboardOperationalTest.php
tests/Feature/TaskManagementTest.php

بعد از جایگزینی:
php artisan view:clear
php artisan optimize:clear
php artisan test --filter=DashboardOperationalTest --colors=never
php artisan test --filter=TaskManagementTest --colors=never
php artisan test --colors=never
