<?php

declare(strict_types=1);

namespace WHMCS\Module\Addon\Buscacep;

use GuzzleHttp\Client;
use Illuminate\Database\Capsule\Manager as Capsule;
use WHMCS\Module\Addon\Buscacep\Providers\BrasilApiProvider;
use WHMCS\Module\Addon\Buscacep\Providers\CepProviderInterface;
use WHMCS\Module\Addon\Buscacep\Providers\ViaCepProvider;

defined('WHMCS') or die('Access Denied');

final class CepService
{
    private const CACHE_TABLE = 'mod_buscacep_cache';

    /** @var list<CepProviderInterface> */
    private array $providers;

    /**
     * @param list<CepProviderInterface>|null $providers
     */
    public function __construct(?array $providers = null)
    {
        $this->providers = $providers ?? $this->defaultProviders();
    }

    public function lookup(string $cep): CepLookupResult
    {
        if (!CepNormalizer::isValid($cep)) {
            return CepLookupResult::fail('invalid');
        }

        $digits = CepNormalizer::digits($cep);

        $cached = $this->fromCache($digits);
        if ($cached !== null) {
            return $cached;
        }

        $sawNotFound = false;
        $sawTransportError = false;

        foreach ($this->providers as $provider) {
            try {
                $mapped = $provider->lookup($digits);
                if ($mapped === null) {
                    $sawNotFound = true;
                    continue;
                }

                if ($mapped['city'] === '' && $mapped['state'] === '') {
                    $sawNotFound = true;
                    continue;
                }

                $result = $this->toResult($mapped, $provider->name(), $digits);
                $this->storeCache($digits, $result);

                return $result;
            } catch (\RuntimeException) {
                $sawTransportError = true;
            }
        }

        if ($sawNotFound) {
            return CepLookupResult::fail('not_found');
        }

        return CepLookupResult::fail($sawTransportError ? 'network' : 'not_found');
    }

    /**
     * @return list<CepProviderInterface>
     */
    private function defaultProviders(): array
    {
        $http = new Client([
            'timeout' => Settings::requestTimeout(),
            'connect_timeout' => min(Settings::requestTimeout(), 5.0),
            'http_errors' => true,
            'headers' => [
                'Accept' => 'application/json',
                'User-Agent' => 'SeixasTec-BuscaCEP/1.0 (WHMCS)',
            ],
        ]);

        return [
            new ViaCepProvider($http),
            new BrasilApiProvider($http),
        ];
    }

    /**
     * @param array{cep: string, address1: string, address2: string, city: string, state: string} $mapped
     */
    private function toResult(array $mapped, string $provider, string $digits): CepLookupResult
    {
        $cep = $mapped['cep'] !== ''
            ? CepNormalizer::format($mapped['cep'])
            : CepNormalizer::format($digits);
        $noStreet = $mapped['address1'] === '';

        return new CepLookupResult(
            ok: true,
            code: $noStreet ? 'no_street' : null,
            cep: $cep,
            address1: $mapped['address1'],
            address2: $mapped['address2'],
            city: $mapped['city'],
            state: $mapped['state'],
            provider: $provider,
            noStreet: $noStreet,
        );
    }

    private function fromCache(string $digits): ?CepLookupResult
    {
        try {
            if (!Capsule::schema()->hasTable(self::CACHE_TABLE)) {
                return null;
            }

            $row = Capsule::table(self::CACHE_TABLE)
                ->where('cep', $digits)
                ->first();

            if ($row === null) {
                return null;
            }

            $updated = strtotime((string) $row->updated_at) ?: 0;
            $ttl = Settings::cacheTtlDays() * 86400;
            if ($updated + $ttl < time()) {
                return null;
            }

            $payload = json_decode((string) $row->payload, true);
            if (!is_array($payload)) {
                return null;
            }

            return new CepLookupResult(
                ok: true,
                code: empty($payload['address1']) ? 'no_street' : null,
                cep: (string) ($payload['cep'] ?? CepNormalizer::format($digits)),
                address1: (string) ($payload['address1'] ?? ''),
                address2: (string) ($payload['address2'] ?? ''),
                city: (string) ($payload['city'] ?? ''),
                state: (string) ($payload['state'] ?? ''),
                provider: (string) ($row->provider ?? 'cache'),
                noStreet: empty($payload['address1']),
            );
        } catch (\Throwable) {
            return null;
        }
    }

    private function storeCache(string $digits, CepLookupResult $result): void
    {
        try {
            if (!Capsule::schema()->hasTable(self::CACHE_TABLE)) {
                return;
            }

            $now = date('Y-m-d H:i:s');
            $payload = json_encode([
                'cep' => $result->cep,
                'address1' => $result->address1,
                'address2' => $result->address2,
                'city' => $result->city,
                'state' => $result->state,
            ], JSON_UNESCAPED_UNICODE);

            $existing = Capsule::table(self::CACHE_TABLE)
                ->where('cep', $digits)
                ->first();

            if ($existing) {
                Capsule::table(self::CACHE_TABLE)
                    ->where('cep', $digits)
                    ->update([
                        'payload' => $payload,
                        'provider' => $result->provider,
                        'updated_at' => $now,
                    ]);

                return;
            }

            Capsule::table(self::CACHE_TABLE)->insert([
                'cep' => $digits,
                'payload' => $payload,
                'provider' => $result->provider,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        } catch (\Throwable $e) {
            if (function_exists('logActivity') && Settings::enabled('enableDebug', false)) {
                logActivity('BuscaCEP: cache write failed — ' . $e->getMessage());
            }
        }
    }

    public function cacheCount(): int
    {
        try {
            if (!Capsule::schema()->hasTable(self::CACHE_TABLE)) {
                return 0;
            }

            return (int) Capsule::table(self::CACHE_TABLE)->count();
        } catch (\Throwable) {
            return 0;
        }
    }

    public function clearCache(): bool
    {
        try {
            if (!Capsule::schema()->hasTable(self::CACHE_TABLE)) {
                return true;
            }

            Capsule::table(self::CACHE_TABLE)->delete();

            return true;
        } catch (\Throwable) {
            return false;
        }
    }
}
