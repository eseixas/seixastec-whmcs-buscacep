<?php

declare(strict_types=1);

namespace WHMCS\Module\Addon\Buscacep;

defined('WHMCS') or die('Access Denied');

final class RateLimiter
{
    private const SESSION_KEY = 'buscacep_rl';
    private const WINDOW = 60;
    private const MAX_HITS = 30;

    public static function hit(): bool
    {
        $now = time();
        $bucket = $_SESSION[self::SESSION_KEY] ?? ['start' => $now, 'count' => 0];

        if (!is_array($bucket) || ($now - (int) ($bucket['start'] ?? 0)) >= self::WINDOW) {
            $bucket = ['start' => $now, 'count' => 0];
        }

        $bucket['count'] = (int) ($bucket['count'] ?? 0) + 1;
        $_SESSION[self::SESSION_KEY] = $bucket;

        return $bucket['count'] <= self::MAX_HITS;
    }
}
