<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../init.php';
require_once __DIR__ . '/lib/autoload.php';

use WHMCS\Module\Addon\Buscacep\CepService;
use WHMCS\Module\Addon\Buscacep\Csrf;
use WHMCS\Module\Addon\Buscacep\RateLimiter;
use WHMCS\Module\Addon\Buscacep\Settings;

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store');

/**
 * @param array<string, mixed> $payload
 */
function buscacep_json(int $status, array $payload): never
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    buscacep_json(405, ['ok' => false, 'code' => 'method']);
}

if (!Settings::isActive()) {
    buscacep_json(403, ['ok' => false, 'code' => 'inactive']);
}

$token = (string) ($_POST['token'] ?? '');
if (!Csrf::verify($token)) {
    buscacep_json(403, ['ok' => false, 'code' => 'csrf']);
}

if (!RateLimiter::hit()) {
    buscacep_json(429, ['ok' => false, 'code' => 'rate_limit']);
}

$cep = (string) ($_POST['cep'] ?? '');
$result = (new CepService())->lookup($cep);

buscacep_json(200, $result->toArray());
