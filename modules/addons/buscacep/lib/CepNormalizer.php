<?php

declare(strict_types=1);

namespace WHMCS\Module\Addon\Buscacep;

defined('WHMCS') or die('Access Denied');

final class CepNormalizer
{
    public static function digits(string $cep): string
    {
        return preg_replace('/\D+/', '', $cep) ?? '';
    }

    public static function isValid(string $cep): bool
    {
        $digits = self::digits($cep);

        return strlen($digits) === 8 && $digits !== '00000000';
    }

    public static function format(string $cep): string
    {
        $digits = self::digits($cep);
        if (strlen($digits) !== 8) {
            return $cep;
        }

        return substr($digits, 0, 5) . '-' . substr($digits, 5, 3);
    }
}
