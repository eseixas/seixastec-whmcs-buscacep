# Changelog

## 1.0.0 — 2026-09-11

- First release for WHMCS 9 / PHP 8.2+.
- CEP lookup via ViaCEP with BrasilAPI fallback.
- Auto-fill of street, neighborhood, city and state on client and admin address forms.
- Warnings only: invalid or missing CEP never blocks checkout or registration.
- Local cache table `mod_buscacep_cache` (kept on deactivate).
- Admin test page and cache clear action.
