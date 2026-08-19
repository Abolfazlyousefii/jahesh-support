<?php

namespace App\Support;

final class GeneralSettingsCatalog
{
    /** @return array<string,array{group:string,type:string,default:mixed,label:string}> */
    public static function definitions(): array
    {
        return [
            'general.company_name' => [
                'group' => 'general',
                'type' => 'string',
                'default' => 'تیم جهش',
                'label' => 'نام مجموعه',
            ],
            'general.app_name' => [
                'group' => 'general',
                'type' => 'string',
                'default' => 'سامانه پشتیبانی جهش',
                'label' => 'عنوان نرم‌افزار',
            ],
            'general.support_phone' => [
                'group' => 'general',
                'type' => 'string',
                'default' => '',
                'label' => 'شماره پشتیبانی',
            ],
            'general.support_hours' => [
                'group' => 'general',
                'type' => 'string',
                'default' => '',
                'label' => 'ساعات پاسخگویی',
            ],
            'general.support_text' => [
                'group' => 'general',
                'type' => 'string',
                'default' => 'تیم پشتیبانی آماده پاسخ‌گویی به شماست.',
                'label' => 'متن کوتاه پشتیبانی',
            ],
            'general.pagination_per_page' => [
                'group' => 'general',
                'type' => 'integer',
                'default' => 20,
                'label' => 'تعداد آیتم در هر صفحه',
            ],
            'portal.title' => [
                'group' => 'portal',
                'type' => 'string',
                'default' => 'پشتیبانی جهش',
                'label' => 'عنوان پنل مشتری',
            ],
            'portal.welcome_text' => [
                'group' => 'portal',
                'type' => 'string',
                'default' => 'خوش آمدید به پنل پشتیبانی جهش',
                'label' => 'متن خوش‌آمدگویی',
            ],
            'portal.show_support_phone' => [
                'group' => 'portal',
                'type' => 'boolean',
                'default' => true,
                'label' => 'نمایش شماره پشتیبانی',
            ],
            'portal.show_support_hours' => [
                'group' => 'portal',
                'type' => 'boolean',
                'default' => true,
                'label' => 'نمایش ساعات پاسخگویی',
            ],
            'portal.active_ticket_limit' => [
                'group' => 'portal',
                'type' => 'integer',
                'default' => 8,
                'label' => 'تعداد تیکت‌های باز در داشبورد مشتری',
            ],
        ];
    }

    /** @return array<string,mixed> */
    public static function defaults(): array
    {
        return collect(self::definitions())
            ->mapWithKeys(fn (array $definition, string $key) => [$key => $definition['default']])
            ->all();
    }

    /** @return array<string> */
    public static function keys(): array
    {
        return array_keys(self::definitions());
    }
}
