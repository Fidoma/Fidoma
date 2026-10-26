<?php

namespace App\Features\Settings\Services;

use Illuminate\Support\Facades\DB;

class ModuleSettings
{
    private const SETTING_PREFIX = 'module_enabled_';

    /** @var list<string> */
    private const MODULES = [
        'dashboard',
        'clients',
        'invoices',
        'quotes',
        'products',
        'projects',
        'contracts',
        'reservations',
        'bank_transactions',
        'assignments',
        'payments',
        'terminals',
        'reports',
        'tourism_statistics',
        'data_import',
        'webmail',
        'paperless',
    ];

    /** @return list<string> */
    public function modules(): array
    {
        return self::MODULES;
    }

    public function isEnabled(string $module): bool
    {
        $storedValue = DB::table('ip_settings')
            ->where('setting_key', $this->settingKey($module))
            ->value('setting_value');

        return $storedValue === null || $storedValue === '1';
    }

    /** @return array<string, bool> */
    public function all(): array
    {
        $storedSettings = DB::table('ip_settings')
            ->whereIn('setting_key', array_map($this->settingKey(...), self::MODULES))
            ->pluck('setting_value', 'setting_key');

        return collect(self::MODULES)
            ->mapWithKeys(fn (string $module): array => [
                $module => $storedSettings->get($this->settingKey($module), '1') === '1',
            ])
            ->all();
    }

    /** @param array<string, bool> $modules */
    public function save(array $modules): void
    {
        foreach (self::MODULES as $module) {
            DB::table('ip_settings')->updateOrInsert(
                ['setting_key' => $this->settingKey($module)],
                ['setting_value' => ($modules[$module] ?? false) ? '1' : '0'],
            );
        }

    }

    private function settingKey(string $module): string
    {
        return self::SETTING_PREFIX.$module;
    }
}
