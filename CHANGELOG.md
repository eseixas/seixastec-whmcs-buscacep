# Changelog

## 1.0.0 — 2026-09-11

- Primeira versão para WHMCS 9 / PHP 8.2+.
- Consulta de CEP via ViaCEP, com fallback na BrasilAPI.
- Preenchimento de logradouro, bairro, cidade e UF na área do cliente e no admin.
- Avisos visuais apenas: CEP inválido não bloqueia checkout nem cadastro.
- Tabela de cache `mod_buscacep_cache` (preservada na desativação).
- Página admin para testar CEP e limpar cache.
