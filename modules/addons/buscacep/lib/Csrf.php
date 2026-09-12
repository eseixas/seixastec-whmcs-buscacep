<?php

declare(strict_types=1);

namespace WHMCS\Module\Addon\Buscacep;

defined('WHMCS') or die('Access Denied');

final class Csrf
{
    private const SESSION_KEY = 'buscacep_csrf';

    public static function token(): string
    {
        $existing = $_SESSION[self::SESSION_KEY] ?? '';
        if (is_string($existing) && strlen($existing) === 64) {
            return $existing;
        }

        $token = bin2hex(random_bytes(32));
        $_SESSION[self::SESSION_KEY] = $token;

        return $token;
    }

    public static function verify(string $provided): bool
    {
        $expected = $_SESSION[self::SESSION_KEY] ?? '';
        if (!is_string($expected) || $expected === '' || $provided === '') {
            return false;
        }

        return hash_equals($expected, $provided);
    }
}
