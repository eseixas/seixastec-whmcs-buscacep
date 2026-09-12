<?php

declare(strict_types=1);

namespace WHMCS\Module\Addon\Buscacep\Providers;

defined('WHMCS') or die('Access Denied');

interface CepProviderInterface
{
    public function name(): string;

    /**
     * @return array{
     *     cep: string,
     *     address1: string,
     *     address2: string,
     *     city: string,
     *     state: string
     * }|null Null when the CEP is not found at this provider.
     *
     * @throws \RuntimeException On transport or unexpected API errors.
     */
    public function lookup(string $digits): ?array;
}
