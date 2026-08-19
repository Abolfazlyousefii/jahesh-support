<?php

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;

class UpdateGeneralSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('settings.general.manage') === true;
    }

    /** @return array<string,array<int,string>> */
    public function rules(): array
    {
        return [
            'company_name' => ['required', 'string', 'max:120'],
            'app_name' => ['required', 'string', 'max:120'],
            'support_phone' => ['nullable', 'string', 'max:50'],
            'support_hours' => ['nullable', 'string', 'max:120'],
            'support_text' => ['nullable', 'string', 'max:500'],
            'pagination_per_page' => ['required', 'integer', 'min:10', 'max:100'],
            'portal_title' => ['required', 'string', 'max:120'],
            'portal_welcome_text' => ['required', 'string', 'max:250'],
            'portal_show_support_phone' => ['required', 'boolean'],
            'portal_show_support_hours' => ['required', 'boolean'],
            'portal_active_ticket_limit' => ['required', 'integer', 'min:3', 'max:20'],
        ];
    }

    /** @return array<string,string> */
    public function attributes(): array
    {
        return [
            'company_name' => 'نام مجموعه',
            'app_name' => 'عنوان نرم‌افزار',
            'support_phone' => 'شماره پشتیبانی',
            'support_hours' => 'ساعات پاسخگویی',
            'support_text' => 'متن کوتاه پشتیبانی',
            'pagination_per_page' => 'تعداد آیتم در هر صفحه',
            'portal_title' => 'عنوان پنل مشتری',
            'portal_welcome_text' => 'متن خوش‌آمدگویی',
            'portal_show_support_phone' => 'نمایش شماره پشتیبانی',
            'portal_show_support_hours' => 'نمایش ساعات پاسخگویی',
            'portal_active_ticket_limit' => 'تعداد تیکت‌های داشبورد مشتری',
        ];
    }
}
