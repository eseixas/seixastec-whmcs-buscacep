<?php

declare(strict_types=1);

namespace WHMCS\Module\Addon\Buscacep;

use Illuminate\Database\Capsule\Manager as Capsule;

defined('WHMCS') or die('Access Denied');

final class PostcodeNormalizer
{
    public static function formatClient(int $clientId, string $country, string $postcode): void
    {
        self::update('tblclients', 'id', $clientId, $country, $postcode);
    }

    public static function formatContact(int $contactId, string $country, string $postcode): void
    {
        self::update('tblcontacts', 'id', $contactId, $country, $postcode);
    }

    private static function update(
        string $table,
        string $idColumn,
        int $id,
        string $country,
        string $postcode
    ): void {
        if ($id < 1 || strtoupper($country) !== 'BR' || !CepNormalizer::isValid($postcode)) {
            return;
        }

        $formatted = CepNormalizer::format($postcode);
        if ($formatted === $postcode) {
            return;
        }

        try {
            Capsule::table($table)
                ->where($idColumn, $id)
                ->update(['postcode' => $formatted]);
        } catch (\Throwable $e) {
            if (function_exists('logActivity') && Settings::enabled('enableDebug', false)) {
                logActivity('BuscaCEP: postcode normalize failed — ' . $e->getMessage());
            }
        }
    }
}
