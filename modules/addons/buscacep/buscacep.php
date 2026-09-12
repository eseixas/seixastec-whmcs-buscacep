<?php

declare(strict_types=1);

defined('WHMCS') or die('Access Denied');

use Illuminate\Database\Capsule\Manager as Capsule;
use WHMCS\Module\Addon\Buscacep\CepService;
use WHMCS\Module\Addon\Buscacep\Settings;

require_once __DIR__ . '/lib/autoload.php';

function buscacep_config(): array
{
    return [
        'name' => 'SeixasTec BuscaCEP',
        'description' => 'Valida o CEP brasileiro e preenche o endereço via ViaCEP, com fallback na BrasilAPI.',
        'version' => '1.0.0',
        'author' => 'SeixasTec',
        'language' => 'english',
        'fields' => [
            'enableClientArea' => [
                'FriendlyName' => 'Área do cliente',
                'Type' => 'yesno',
                'Default' => 'on',
                'Description' => 'Ativar busca de CEP no checkout, cadastro, perfil e contatos.',
            ],
            'enableAdminArea' => [
                'FriendlyName' => 'Área administrativa',
                'Type' => 'yesno',
                'Default' => 'on',
                'Description' => 'Ativar busca de CEP ao cadastrar/editar clientes e contatos no admin.',
            ],
            'enableCepMask' => [
                'FriendlyName' => 'Máscara de CEP',
                'Type' => 'yesno',
                'Default' => 'on',
                'Description' => 'Formatar o campo como 00000-000 enquanto o usuário digita.',
            ],
            'cacheTtlDays' => [
                'FriendlyName' => 'TTL do cache (dias)',
                'Type' => 'text',
                'Size' => '5',
                'Default' => '30',
                'Description' => 'Por quanto tempo um CEP encontrado fica em cache local.',
            ],
            'requestTimeout' => [
                'FriendlyName' => 'Timeout da API (segundos)',
                'Type' => 'text',
                'Size' => '5',
                'Default' => '5',
                'Description' => 'Tempo máximo de espera por ViaCEP/BrasilAPI.',
            ],
            'enableDebug' => [
                'FriendlyName' => 'Modo debug',
                'Type' => 'yesno',
                'Description' => 'Registra falhas extras no Activity Log (chamadas de API já vão para o Module Log).',
            ],
        ],
    ];
}

function buscacep_activate(): array
{
    try {
        if (!Capsule::schema()->hasTable('mod_buscacep_cache')) {
            Capsule::schema()->create('mod_buscacep_cache', function ($table) {
                /** @var \Illuminate\Database\Schema\Blueprint $table */
                $table->increments('id');
                $table->string('cep', 8);
                $table->text('payload');
                $table->string('provider', 32)->nullable();
                $table->timestamp('created_at')->nullable();
                $table->timestamp('updated_at')->nullable();
                $table->unique('cep');
            });
        }

        return [
            'status' => 'success',
            'description' => 'BuscaCEP ativado. Configure as opções acima e teste um CEP na página do módulo.',
        ];
    } catch (\Throwable $e) {
        return [
            'status' => 'error',
            'description' => 'Falha ao criar a tabela de cache: ' . $e->getMessage(),
        ];
    }
}

function buscacep_deactivate(): array
{
    return [
        'status' => 'success',
        'description' => 'BuscaCEP desativado. A tabela de cache foi preservada.',
    ];
}

function buscacep_upgrade(array $vars): void
{
    $currentVersion = (string) ($vars['version'] ?? '1.0.0');

    if (version_compare($currentVersion, '1.0.0', '<')) {
        buscacep_activate();
    }
}

function buscacep_output(array $vars): void
{
    $lang = is_array($vars['_lang'] ?? null) ? $vars['_lang'] : [];
    $service = new CepService();
    $notice = '';
    $noticeType = 'info';
    $testCep = '';
    $testResult = null;

    $action = (string) ($_POST['buscacep_action'] ?? '');
    if ($action !== '') {
        if (!buscacep_admin_token_ok()) {
            $notice = $lang['invalid_token'] ?? 'Invalid token';
            $noticeType = 'danger';
        } elseif ($action === 'clear_cache') {
            if ($service->clearCache()) {
                $notice = $lang['cache_cleared'] ?? 'Cache cleared.';
                $noticeType = 'success';
            } else {
                $notice = $lang['cache_clear_failed'] ?? 'Failed to clear cache.';
                $noticeType = 'danger';
            }
        } elseif ($action === 'test') {
            $testCep = (string) ($_POST['cep'] ?? '');
            $testResult = $service->lookup($testCep)->toArray();
        }
    }

    echo buscacep_render_admin('dashboard', [
        'lang' => $lang,
        'moduleLink' => (string) ($vars['modulelink'] ?? ''),
        'version' => (string) ($vars['version'] ?? '1.0.0'),
        'csrfToken' => function_exists('generate_token') ? generate_token('plain') : '',
        'enableClientArea' => Settings::enabled('enableClientArea'),
        'enableAdminArea' => Settings::enabled('enableAdminArea'),
        'enableCepMask' => Settings::enabled('enableCepMask'),
        'cacheTtlDays' => Settings::cacheTtlDays(),
        'requestTimeout' => Settings::requestTimeout(),
        'cacheCount' => $service->cacheCount(),
        'notice' => $notice,
        'noticeType' => $noticeType,
        'testCep' => $testCep,
        'testResult' => $testResult,
    ]);
}

function buscacep_admin_token_ok(): bool
{
    if (!function_exists('check_token')) {
        return true;
    }

    return (bool) check_token('WHMCS.default');
}

/**
 * @param array<string, mixed> $assigns
 */
function buscacep_render_admin(string $template, array $assigns): string
{
    $templateDir = __DIR__ . '/templates/admin/';

    try {
        $compileDir = $GLOBALS['templates_compiledir'] ?? sys_get_temp_dir();

        if (class_exists(\Smarty\Smarty::class)) {
            $smarty = new \Smarty\Smarty();
        } elseif (class_exists(\Smarty::class)) {
            $smarty = new \Smarty();
        } else {
            throw new \RuntimeException('Smarty is not available');
        }

        if (method_exists($smarty, 'setTemplateDir')) {
            $smarty->setTemplateDir($templateDir);
        }
        if (method_exists($smarty, 'setCompileDir')) {
            $smarty->setCompileDir($compileDir);
        }
        if (property_exists($smarty, 'caching')) {
            $smarty->caching = false;
        }

        foreach ($assigns as $key => $value) {
            $smarty->assign($key, $value);
        }

        return $smarty->fetch($template . '.tpl');
    } catch (\Throwable $e) {
        return '<div class="alert alert-danger">BuscaCEP template error: '
            . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8')
            . '</div>';
    }
}
