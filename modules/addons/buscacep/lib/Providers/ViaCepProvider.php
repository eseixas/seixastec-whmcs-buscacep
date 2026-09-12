<?php

declare(strict_types=1);

namespace WHMCS\Module\Addon\Buscacep\Providers;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use WHMCS\Module\Addon\Buscacep\CepNormalizer;
use WHMCS\Module\Addon\Buscacep\Settings;

defined('WHMCS') or die('Access Denied');

final class ViaCepProvider implements CepProviderInterface
{
    public function __construct(private readonly Client $http)
    {
    }

    public function name(): string
    {
        return 'viacep';
    }

    public function lookup(string $digits): ?array
    {
        $url = 'https://viacep.com.br/ws/' . $digits . '/json/';

        try {
            $response = $this->http->get($url);
            $raw = (string) $response->getBody();
            $data = json_decode($raw, true);

            $this->log($digits, $raw, $data);

            if (!is_array($data)) {
                throw new \RuntimeException('ViaCEP returned invalid JSON.');
            }

            if (!empty($data['erro'])) {
                return null;
            }

            return $this->map($data);
        } catch (GuzzleException $e) {
            $this->log($digits, $e->getMessage(), null);
            throw new \RuntimeException('ViaCEP request failed: ' . $e->getMessage(), 0, $e);
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
            'address1' => trim((string) ($data['logradouro'] ?? '')),
            'address2' => trim((string) ($data['bairro'] ?? '')),
            'city' => trim((string) ($data['localidade'] ?? '')),
            'state' => strtoupper(trim((string) ($data['uf'] ?? ''))),
        ];
    }

    private function log(string $cep, mixed $raw, mixed $processed): void
    {
        if (!function_exists('logModuleCall')) {
            return;
        }

        logModuleCall(Settings::MODULE, 'ViaCEP', ['cep' => $cep], $raw, $processed);
    }
}
