<div class="buscacep-admin">
    {if $notice}
        <div class="alert alert-{$noticeType|escape:'html'}">
            {$notice|escape:'html'}
        </div>
    {/if}

    <div class="row">
        <div class="col-md-6">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title">{$lang.status_heading|escape:'html'}</h3>
                </div>
                <div class="panel-body">
                    <table class="table table-condensed" style="margin-bottom:0;">
                        <tr>
                            <th>{$lang.label_version|escape:'html'}</th>
                            <td>{$version|escape:'html'}</td>
                        </tr>
                        <tr>
                            <th>{$lang.label_client_area|escape:'html'}</th>
                            <td>
                                {if $enableClientArea}
                                    <span class="label label-success">{$lang.enabled|escape:'html'}</span>
                                {else}
                                    <span class="label label-default">{$lang.disabled|escape:'html'}</span>
                                {/if}
                            </td>
                        </tr>
                        <tr>
                            <th>{$lang.label_admin_area|escape:'html'}</th>
                            <td>
                                {if $enableAdminArea}
                                    <span class="label label-success">{$lang.enabled|escape:'html'}</span>
                                {else}
                                    <span class="label label-default">{$lang.disabled|escape:'html'}</span>
                                {/if}
                            </td>
                        </tr>
                        <tr>
                            <th>{$lang.label_mask|escape:'html'}</th>
                            <td>
                                {if $enableCepMask}
                                    <span class="label label-success">{$lang.enabled|escape:'html'}</span>
                                {else}
                                    <span class="label label-default">{$lang.disabled|escape:'html'}</span>
                                {/if}
                            </td>
                        </tr>
                        <tr>
                            <th>{$lang.label_cache_ttl|escape:'html'}</th>
                            <td>{$cacheTtlDays|escape:'html'}</td>
                        </tr>
                        <tr>
                            <th>{$lang.label_timeout|escape:'html'}</th>
                            <td>{$requestTimeout|escape:'html'}</td>
                        </tr>
                        <tr>
                            <th>{$lang.label_cache_entries|escape:'html'}</th>
                            <td>{$cacheCount|escape:'html'}</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title">{$lang.test_heading|escape:'html'}</h3>
                </div>
                <div class="panel-body">
                    <p class="text-muted">{$lang.test_help|escape:'html'}</p>
                    <form method="post" action="{$moduleLink|escape:'html'}">
                        <input type="hidden" name="token" value="{$csrfToken|escape:'html'}">
                        <input type="hidden" name="buscacep_action" value="test">
                        <div class="form-group">
                            <label for="buscacep-test-cep">{$lang.label_cep|escape:'html'}</label>
                            <input id="buscacep-test-cep" class="form-control" type="text" name="cep" value="{$testCep|escape:'html'}" maxlength="9" placeholder="01001-000" autocomplete="off">
                        </div>
                        <button type="submit" class="btn btn-primary">{$lang.btn_test|escape:'html'}</button>
                    </form>
                </div>
            </div>

            <form method="post" action="{$moduleLink|escape:'html'}" onsubmit="return confirm('{$lang.confirm_clear_cache|escape:'javascript'}');">
                <input type="hidden" name="token" value="{$csrfToken|escape:'html'}">
                <input type="hidden" name="buscacep_action" value="clear_cache">
                <button type="submit" class="btn btn-default">{$lang.btn_clear_cache|escape:'html'}</button>
            </form>
        </div>
    </div>

    {if $testResult}
        <div class="panel panel-default">
            <div class="panel-heading">
                <h3 class="panel-title">{$lang.result_heading|escape:'html'}</h3>
            </div>
            <div class="panel-body">
                {if $testResult.ok}
                    <div class="alert alert-success">{$lang.result_ok|escape:'html'}</div>
                    <table class="table table-striped">
                        <tr>
                            <th>{$lang.label_cep|escape:'html'}</th>
                            <td>{$testResult.cep|escape:'html'}</td>
                        </tr>
                        <tr>
                            <th>{$lang.field_street|escape:'html'}</th>
                            <td>{$testResult.address1|escape:'html'}</td>
                        </tr>
                        <tr>
                            <th>{$lang.field_neighborhood|escape:'html'}</th>
                            <td>{$testResult.address2|escape:'html'}</td>
                        </tr>
                        <tr>
                            <th>{$lang.field_city|escape:'html'}</th>
                            <td>{$testResult.city|escape:'html'}</td>
                        </tr>
                        <tr>
                            <th>{$lang.field_state|escape:'html'}</th>
                            <td>{$testResult.state|escape:'html'}</td>
                        </tr>
                        <tr>
                            <th>{$lang.field_provider|escape:'html'}</th>
                            <td>{$testResult.provider|escape:'html'}</td>
                        </tr>
                    </table>
                {else}
                    <div class="alert alert-warning">
                        {$lang.result_fail|escape:'html'}
                        {if $testResult.code}
                            ({$testResult.code|escape:'html'})
                        {/if}
                    </div>
                {/if}
            </div>
        </div>
    {/if}
</div>
