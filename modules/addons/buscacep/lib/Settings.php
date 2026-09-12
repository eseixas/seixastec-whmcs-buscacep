<?php

declare(strict_types=1);

namespace WHMCS\Module\Addon\Buscacep;

use Illuminate\Database\Capsule\Manager as Capsule;

defined('WHMCS') or die('Access Denied');

final class Settings
{
    public const MODULE = 'buscacep';

    /** @var array<string, string>|null */
    private static ?array $cache = null;

    public static function isActive(): bool
    {
        try {
            return Capsule::table('tbladdonmodules')
                ->where('module', self::MODULE)
                ->where('setting', 'version')
                ->exists();
        } catch (\Throwable) {
            return false;
        }
    }

    public static function get(string $key, string $default = ''): string
    {
        $all = self::all();

        return array_key_exists($key, $all) ? (string) $all[$key] : $default;
    }

    public static function enabled(string $key, bool $default = true): bool
    {
        $all = self::all();
        if (!array_key_exists($key, $all)) {
            return $default;
        }

        $value = strtolower(trim((string) $all[$key]));

        return in_array($value, ['on', '1', 'yes', 'true'], true);
    }

    /**
     * @return array<string, string>
     */
    public static function all(): array
    {
        if (self::$cache !== null) {
            return self::$cache;
        }

        try {
            self::$cache = Capsule::table('tbladdonmodules')
                ->where('module', self::MODULE)
                ->pluck('value', 'setting')
                ->all();
        } catch (\Throwable) {
            self::$cache = [];
        }

        return self::$cache;
    }

    public static function requestTimeout(): float
    {
        $raw = self::get('requestTimeout', '5');
        $timeout = (float) $raw;

        return $timeout > 0 ? min($timeout, 30) : 5.0;
    }

    public static function cacheTtlDays(): int
    {
        $days = (int) self::get('cacheTtlDays', '30');

        return $days > 0 ? min($days, 365) : 30;
    }

    public static function reset(): void
    {
        self::$cache = null;
    }
}
