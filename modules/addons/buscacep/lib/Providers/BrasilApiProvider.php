<?php

declare(strict_types=1);

namespace WHMCS\Module\Addon\Buscacep\Providers;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use WHMCS\Module\Addon\Buscacep\CepNormalizer;
use WHMCS\Module\Addon\Buscacep\Settings;

defined('WHMCS') or die('Access Denied');

final class BrasilApiProvider implements CepProviderInterface
{
    public function __construct(private readonly Client $http)
    {
    }

    public function name(): string
    {
        return 'brasilapi';
    }

    public function lookup(string $digits): ?array
    {
        $url = 'https://brasilapi.com.br/api/cep/v2/' . $digits;

        try {
            $response = $this->http->get($url);
            $raw = (string) $response->getBody();
            $data = json_decode($raw, true);

            $this->log($digits, $raw, $data);

            if (!is_array($data)) {
                throw new \RuntimeException('BrasilAPI returned invalid JSON.');
            }

            return $this->map($data);
        } catch (RequestException $e) {
            $status = $e->hasResponse() ? $e->getResponse()?->getStatusCode() : null;
            $body = $e->hasResponse() ? (string) $e->getResponse()?->getBody() : $e->getMessage();
            $this->log($digits, $body, ['status' => $status]);

            if ($status === 404) {
                return null;
            }

            throw new \RuntimeException('BrasilAPI request failed: ' . $e->getMessage(), 0, $e);
        } catch (GuzzleException $e) {
            $this->log($digits, $e->getMessage(), null);
            throw new \RuntimeException('BrasilAPI request failed: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * @param array<string, mixed> $data
     * @return array{cep: string, address1: string, address2: string, city: string, state: string}
     */
    private function map(array $data): array
    {
        $cep = CepNormalizer::format((string) ($data['cep'] ?? ''));

        return [
            'cep' => $cep,
            'address1' => trim((string) ($data['street'] ?? '')),
            'address2' => trim((string) ($data['neighborhood'] ?? '')),
            'city' => trim((string) ($data['city'] ?? '')),
            'state' => strtoupper(trim((string) ($data['state'] ?? ''))),
        ];
    }

    private function log(string $cep, mixed $raw, mixed $processed): void
    {
        if (!function_exists('logModuleCall')) {
            return;
        }

        logModuleCall(Settings::MODULE, 'BrasilAPI', ['cep' => $cep], $raw, $processed);
    }
}
