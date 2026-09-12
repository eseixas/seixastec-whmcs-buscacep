# Instalação

## Requisitos

- WHMCS 9.x
- PHP 8.2+ (8.3 recomendado)
- ionCube Loader na versão exigida pelo WHMCS
- Saída HTTPS para `viacep.com.br` e `brasilapi.com.br`

## Passo a passo

1. Copie a pasta `modules/addons/buscacep` deste repositório para:

   ```
   <raiz-do-whmcs>/modules/addons/buscacep
   ```

   O nome da pasta e o arquivo `buscacep.php` precisam coincidir.

2. No admin do WHMCS: **System Settings → Addon Modules**.

3. Clique em **Activate** em **SeixasTec BuscaCEP**.

4. Marque o grupo de administradores com permissão de acesso e **Save Changes**.

5. Abra **Addons → SeixasTec BuscaCEP** e consulte um CEP de teste (`01001-000`).

Se o `hooks.php` for colocado no servidor **depois** da ativação, desative e ative o módulo de novo. O WHMCS só passa a carregar hooks de addon na ativação (ou ao regravar as opções).

## Configuração

| Opção | Padrão | Função |
|--------|--------|--------|
| Área do cliente | Sim | Checkout, cadastro, perfil e contatos |
| Área administrativa | Sim | Cadastro/edição de cliente e contato |
| Máscara de CEP | Sim | Formato `00000-000` |
| TTL do cache | 30 dias | Cache local de CEPs encontrados |
| Timeout da API | 5 s | Tempo máximo por provedor |
| Modo debug | Não | Extra no Activity Log |

A consulta AJAX usa `modules/addons/buscacep/lookup.php` (CSRF de sessão, rate limit de 30/min, cache Capsule).

## Checklist de teste

- [ ] CEP válido: `01001-000` (Praça da Sé, São Paulo/SP)
- [ ] CEP inexistente: `00000-001` — aviso, formulário ainda envia
- [ ] CEP genérico (sem logradouro) — preenche cidade/UF e avisa
- [ ] País diferente de Brasil — não consulta
- [ ] Checkout deslogado, `register.php`, perfil do cliente, admin `clientsadd.php`
- [ ] Module Log em **Configuration → System Logs → Module Log** após uma consulta

## Desinstalação

1. Desative o addon em **Addon Modules**.
2. A tabela `mod_buscacep_cache` **não** é apagada na desativação.
3. Para remover o cache:

   ```sql
   DROP TABLE IF EXISTS mod_buscacep_cache;
   ```

4. Apague `modules/addons/buscacep`.
