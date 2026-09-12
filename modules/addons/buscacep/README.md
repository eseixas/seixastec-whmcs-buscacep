# SeixasTec BuscaCEP

Addon para WHMCS 9 que valida o CEP brasileiro e preenche o endereço automaticamente.

Documentação completa do repositório: [README.md](../../../README.md), [instalação](../../../docs/instalacao.md), [arquitetura](../../../docs/arquitetura.md).

- **Consulta:** ViaCEP, com fallback na BrasilAPI
- **Telas:** checkout, cadastro, perfil, contatos, admin (cliente e contato)
- **CEP inválido:** apenas aviso visual — não bloqueia o envio do formulário
- **Requisitos:** WHMCS 9.x, PHP 8.2+, ionCube conforme o WHMCS

## Instalação

1. Copie a pasta `buscacep` para `modules/addons/buscacep` na instalação WHMCS.
2. No admin: **System Settings → Addon Modules**.
3. Clique em **Activate** em **SeixasTec BuscaCEP**.
4. Marque o grupo de administradores com acesso e salve.
5. Abra **Addons → SeixasTec BuscaCEP** e teste um CEP (ex.: `01001-000`).

Se o arquivo `hooks.php` for adicionado depois da ativação, desative e ative o módulo de novo.

## Configuração

| Opção | Padrão | Função |
|--------|--------|--------|
| Área do cliente | Sim | Checkout, cadastro, perfil e contatos |
| Área administrativa | Sim | Cadastro/edição de cliente e contato |
| Máscara de CEP | Sim | Formato `00000-000` |
| TTL do cache | 30 dias | Cache local de CEPs encontrados |
| Timeout da API | 5 s | Tempo máximo por provedor |
| Modo debug | Não | Extra no Activity Log |

A consulta **não** é feita no navegador. O JavaScript chama `modules/addons/buscacep/lookup.php`, que aplica CSRF, rate limit (30/min) e cache.

## Mapeamento de campos

| API | Campo WHMCS |
|-----|-------------|
| logradouro / street | `address1` |
| bairro / neighborhood | `address2` |
| localidade / city | `city` |
| uf / state | `state` |
| cep | `postcode` |

O número do imóvel não vem da API e não é inventado. Se o logradouro já estiver preenchido com o mesmo nome + número, o número é preservado.

A busca só roda quando o país do formulário é Brasil (`BR`) ou ainda está vazio. País vazio é definido como `BR` após um CEP válido.

## Checklist de teste

- [ ] CEP válido: `01001-000` (Praça da Sé, São Paulo/SP)
- [ ] CEP inexistente: `00000-001` — aviso, formulário ainda envia
- [ ] CEP genérico (sem logradouro) — preenche cidade/UF e avisa
- [ ] País diferente de Brasil — não consulta
- [ ] Checkout deslogado, `register.php`, perfil do cliente, admin `clientsadd.php`
- [ ] Module Log em **Configuration → System Logs → Module Log** após uma consulta

## Segurança

- Guard `defined('WHMCS')` nos PHP do módulo
- CSRF próprio de sessão no endpoint AJAX
- Token WHMCS nas ações do admin
- Rate limit por sessão
- Capsule/Eloquent para o cache (`mod_buscacep_cache`)
- Desativar o módulo **não** apaga a tabela de cache

## Desinstalação

Desative o addon em Addon Modules. Para remover o cache:

```sql
DROP TABLE IF EXISTS mod_buscacep_cache;
```

Depois apague `modules/addons/buscacep`.
