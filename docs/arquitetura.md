# Arquitetura

## Fluxo

```
Formulário WHMCS (CEP)
        │  blur / 8 dígitos
        ▼
js/buscacep.js  --POST-->  lookup.php
                              │
                              ├─ CSRF de sessão
                              ├─ rate limit (30/min)
                              ├─ cache mod_buscacep_cache
                              ├─ ViaCEP (Guzzle)
                              └─ fallback BrasilAPI
                              ▼
                         JSON { ok, code?, address? }
        │
        ▼
Preenche address1, address2, city, state
(+ aviso visual se inválido / não encontrado)
```

CEP inválido **não** bloqueia `ShoppingCartValidateCheckout` nem `ClientDetailsValidation`.

## Arquivos do módulo

```
modules/addons/buscacep/
├── buscacep.php              # config, activate, deactivate, upgrade, admin
├── hooks.php                 # injeta JS/CSS; normaliza CEP no save
├── lookup.php                # endpoint JSON (init.php do WHMCS)
├── js/buscacep.js
├── css/buscacep.css
├── lang/english.php
├── lang/portuguese-br.php
├── lib/                      # normalizer, providers, cache, CSRF
└── templates/admin/dashboard.tpl
```

## Mapeamento

| ViaCEP / BrasilAPI | Campo WHMCS |
|--------------------|-------------|
| `cep` | `postcode` |
| `logradouro` / `street` | `address1` |
| `bairro` / `neighborhood` | `address2` |
| `localidade` / `city` | `city` |
| `uf` / `state` | `state` |

País: só consulta se o valor for `BR` ou vazio. País vazio é definido como `BR` após um CEP válido.

CEP genérico (sem logradouro): preenche cidade/UF/bairro se existirem e avisa para completar o endereço.

## Provedores

1. **ViaCEP** — `GET https://viacep.com.br/ws/{cep}/json/`
2. **BrasilAPI** — `GET https://brasilapi.com.br/api/cep/v2/{cep}` (se o primeiro falhar)

CEPs encontrados entram em `mod_buscacep_cache` (TTL configurável). Respostas negativas **não** são cacheadas.

## Hooks

- `ClientAreaFooterOutput` — JS/CSS na área do cliente
- `AdminAreaFooterOutput` — JS/CSS em `clients`, `clientsadd`, `clientscontacts`
- `ClientAdd` / `ClientEdit` / `ContactAdd` / `ContactEdit` — normaliza CEP para `NNNNN-NNN` quando o país é BR (não recusa o save)

## Segurança

- `defined('WHMCS')` em todos os PHP do módulo, exceto `lookup.php` (que carrega `init.php`)
- CSRF de sessão no AJAX; token WHMCS nas ações do admin
- Rate limit por sessão
- Capsule com binding para o cache
- `logModuleCall` em cada chamada externa
- Sem chaves de API nesta versão
