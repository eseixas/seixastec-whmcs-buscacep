<?php

declare(strict_types=1);

defined('WHMCS') or die('Access Denied');

spl_autoload_register(static function (string $class): void {
    $prefix = 'WHMCS\\Module\\Addon\\Buscacep\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $relative = str_replace('\\', DIRECTORY_SEPARATOR, substr($class, strlen($prefix)));
    $file = __DIR__ . DIRECTORY_SEPARATOR . $relative . '.php';

    if (is_file($file)) {
        require_once $file;
    }
});
