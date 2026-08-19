<?php

namespace App\Services\Settings;

use App\Models\GeneralSetting;
use App\Support\GeneralSettingsCatalog;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class SettingsService
{
    private const CACHE_KEY = 'jahesh.general_settings.v1';

    /** @return array<string,mixed> */
    public function all(): array
    {
        return Cache::rememberForever(self::CACHE_KEY, function (): array {
            $settings = GeneralSettingsCatalog::defaults();

            if (! Schema::hasTable('general_settings')) {
                return $settings;
            }

            GeneralSetting::query()
                ->whereIn('key', GeneralSettingsCatalog::keys())
                ->get(['key', 'value', 'type'])
                ->each(function (GeneralSetting $setting) use (&$settings): void {
                    $settings[$setting->key] = $this->cast(
                        $setting->value,
                        $setting->type,
                        $settings[$setting->key] ?? null,
                    );
                });

            return $settings;
        });
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->all()[$key] ?? $default;
    }

    public function string(string $key, string $default = ''): string
    {
        $value = $this->get($key, $default);

        return is_scalar($value) ? (string) $value : $default;
    }

    public function integer(string $key, int $default = 0): int
    {
        return (int) $this->get($key, $default);
    }

    public function boolean(string $key, bool $default = false): bool
    {
        return (bool) $this->get($key, $default);
    }

    public function paginationPerPage(): int
    {
        return max(10, min(100, $this->integer('general.pagination_per_page', 20)));
    }

    public function portalActiveTicketLimit(): int
    {
        return max(3, min(20, $this->integer('portal.active_ticket_limit', 8)));
    }

    /** @param array<string,mixed> $values */
    public function update(array $values): void
    {
        $definitions = GeneralSettingsCatalog::definitions();

        foreach ($values as $key => $value) {
            if (! isset($definitions[$key])) {
                continue;
            }

            $definition = $definitions[$key];

            GeneralSetting::query()->updateOrCreate(
                ['key' => $key],
                [
                    'group' => $definition['group'],
                    'type' => $definition['type'],
                    'value' => $this->serialize($value, $definition['type']),
                ],
            );
        }

        $this->forget();
    }

    public function forget(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    private function serialize(mixed $value, string $type): ?string
    {
        return match ($type) {
            'boolean' => $value ? '1' : '0',
            'integer' => (string) (int) $value,
            default => $value === null ? null : (string) $value,
        };
    }

    private function cast(?string $value, string $type, mixed $default): mixed
    {
        if ($value === null) {
            return $default;
        }

        return match ($type) {
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'integer' => (int) $value,
            default => $value,
        };
    }
}
