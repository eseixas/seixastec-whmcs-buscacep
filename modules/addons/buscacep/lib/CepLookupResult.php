<?php

declare(strict_types=1);

namespace WHMCS\Module\Addon\Buscacep;

defined('WHMCS') or die('Access Denied');

final class CepLookupResult
{
    public function __construct(
        public readonly bool $ok,
        public readonly ?string $code = null,
        public readonly ?string $cep = null,
        public readonly ?string $address1 = null,
        public readonly ?string $address2 = null,
        public readonly ?string $city = null,
        public readonly ?string $state = null,
        public readonly ?string $provider = null,
        public readonly bool $noStreet = false,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'ok' => $this->ok,
            'code' => $this->code,
            'cep' => $this->cep,
            'address1' => $this->address1,
            'address2' => $this->address2,
            'city' => $this->city,
            'state' => $this->state,
            'provider' => $this->provider,
            'noStreet' => $this->noStreet,
        ];
    }

    public static function fail(string $code): self
    {
        return new self(ok: false, code: $code);
    }
}
