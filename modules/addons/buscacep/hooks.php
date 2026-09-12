<?php

declare(strict_types=1);

defined('WHMCS') or die('Access Denied');

use WHMCS\Module\Addon\Buscacep\Csrf;
use WHMCS\Module\Addon\Buscacep\PostcodeNormalizer;
use WHMCS\Module\Addon\Buscacep\Settings;

require_once __DIR__ . '/lib/autoload.php';

add_hook('ClientAreaFooterOutput', 1, static function (array $vars): string {
    if (!Settings::enabled('enableClientArea')) {
        return '';
    }

    $language = (string) ($vars['language'] ?? 'english');

    return buscacep_asset_html($language);
});

add_hook('AdminAreaFooterOutput', 1, static function (array $vars): string {
    if (!Settings::enabled('enableAdminArea')) {
        return '';
    }

    if (!buscacep_is_admin_address_page($vars)) {
        return '';
    }

    $language = (string) ($vars['language'] ?? 'english');

    return buscacep_asset_html($language);
});

add_hook('ClientAdd', 10, static function (array $vars): void {
    PostcodeNormalizer::formatClient(
        (int) ($vars['userid'] ?? 0),
        (string) ($vars['country'] ?? ''),
        (string) ($vars['postcode'] ?? '')
    );
});

add_hook('ClientEdit', 10, static function (array $vars): void {
    PostcodeNormalizer::formatClient(
        (int) ($vars['userid'] ?? 0),
        (string) ($vars['country'] ?? ''),
        (string) ($vars['postcode'] ?? '')
    );
});

add_hook('ContactAdd', 10, static function (array $vars): void {
    PostcodeNormalizer::formatContact(
        (int) ($vars['contactid'] ?? 0),
        (string) ($vars['country'] ?? ''),
        (string) ($vars['postcode'] ?? '')
    );
});

add_hook('ContactEdit', 10, static function (array $vars): void {
    PostcodeNormalizer::formatContact(
        (int) ($vars['contactid'] ?? 0),
        (string) ($vars['country'] ?? ''),
        (string) ($vars['postcode'] ?? '')
    );
});

function buscacep_is_admin_address_page(array $vars): bool
{
    $filename = strtolower((string) ($vars['filename'] ?? ''));
    $filename = preg_replace('/\.php$/', '', $filename) ?? $filename;
    $allowed = ['clients', 'clientsadd', 'clientscontacts'];
    if (in_array($filename, $allowed, true)) {
        return true;
    }

    $script = strtolower((string) ($_SERVER['SCRIPT_NAME'] ?? ''));

    return str_contains($script, 'clientsadd')
        || str_contains($script, 'clientscontacts')
        || str_ends_with($script, 'clients.php');
}

function buscacep_asset_html(string $language): string
{
    $systemUrl = buscacep_system_url();
    $version = Settings::get('version', '1.0.0');
    $config = [
        'endpoint' => $systemUrl . 'modules/addons/buscacep/lookup.php',
        'token' => Csrf::token(),
        'mask' => Settings::enabled('enableCepMask'),
        'country' => 'BR',
        'i18n' => buscacep_js_i18n($language),
    ];

    $json = json_encode(
        $config,
        JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE
    );

    $css = htmlspecialchars($systemUrl . 'modules/addons/buscacep/css/buscacep.css?v=' . $version, ENT_QUOTES, 'UTF-8');
    $js = htmlspecialchars($systemUrl . 'modules/addons/buscacep/js/buscacep.js?v=' . $version, ENT_QUOTES, 'UTF-8');

    return '<link rel="stylesheet" href="' . $css . '">'
        . '<script>window.SeixasTecBuscaCep = ' . $json . ';</script>'
        . '<script src="' . $js . '" defer></script>';
}

/**
 * @return array<string, string>
 */
function buscacep_js_i18n(string $language): array
{
    $strings = buscacep_load_lang('english');
    $normalized = strtolower(str_replace('_', '-', $language));
    if ($normalized !== 'english') {
        $overrideFile = $normalized;
        if (str_starts_with($normalized, 'portuguese')) {
            $overrideFile = 'portuguese-br';
        }
        $strings = array_merge($strings, buscacep_load_lang($overrideFile));
    }

    return [
        'lookingUp' => $strings['js_looking_up'] ?? '',
        'filled' => $strings['js_filled'] ?? '',
        'invalid' => $strings['js_invalid'] ?? '',
        'not_found' => $strings['js_not_found'] ?? '',
        'no_street' => $strings['js_no_street'] ?? '',
        'network' => $strings['js_network'] ?? '',
        'rate_limit' => $strings['js_rate_limit'] ?? '',
        'csrf' => $strings['js_csrf'] ?? '',
    ];
}

/**
 * @return array<string, string>
 */
function buscacep_load_lang(string $language): array
{
    $path = __DIR__ . '/lang/' . $language . '.php';
    if (!is_file($path)) {
        return [];
    }

    $_ADDONLANG = [];
    include $path;

    return is_array($_ADDONLANG) ? $_ADDONLANG : [];
}

function buscacep_system_url(): string
{
    if (class_exists(\App::class) && method_exists(\App::class, 'getSystemURL')) {
        return rtrim((string) \App::getSystemURL(), '/') . '/';
    }

    global $CONFIG;

    return rtrim((string) ($CONFIG['SystemURL'] ?? ''), '/') . '/';
}
