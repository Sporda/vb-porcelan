# VB-porcelan – e-shop Bílé zlato (demo)

E-shop s novým originálním porcelánem Villeroy & Boch za ceny pod doporučenou cenou výrobce.
Pracovní název obchodu **Bílé zlato**. Běží na <https://vb-porcelan.webtitan.cz>.

Demo: objednávka se neplatí, na konci se odešle **nezávazná poptávka** (uloží se do databáze a e-mailem jde na
`INQUIRY_TO`, zatím jan@webtitan.cz). Platby (Stripe) přijdou později.

## Struktura

| Cesta | Co to je |
|---|---|
| `eshop/` | Laravel 13 + SQLite. Obchod (Blade šablony, CSS a JS bez buildu v `public/assets`) a administrace poptávek na `/admin`. |
| `eshop/deploy/panel-deploy.sh` | Skript nasazení, který pouští hosting panel po stažení z GitHubu. |
| `design/` | Historie: návrhy designu (kolo 1–3) a rozcestník s lajky. Klient vybral **návrh 1 – Šalvěj**. Na webu se už neukazují. |
| `scripts/deploy-design.py` | Historie: nahrávání návrhů na server přes API panelu. |

## Spuštění u sebe

```bash
cd eshop
composer install
cp .env.example .env && php artisan key:generate   # jen poprvé
touch database/database.sqlite
php artisan migrate --seed
php artisan serve                                   # http://127.0.0.1:8000
```

Lokálně se e-maily jen zapisují do `storage/logs/laravel.log` (`MAIL_MAILER=log`).

## Nasazení

Hosting panel (panel.webtitan.cz), účet `vbporcelan`, PHP 8.3, kořen webu `public_html/eshop/public`.
Panel stáhne větev `main` z GitHubu (deploy klíč) a pustí `eshop/deploy/panel-deploy.sh`:
`composer install`, migrace, seedery (idempotentní), cache. Závislosti jsou v `composer.json` připnuté na PHP 8.3
(`config.platform.php`), protože server novější verzi nemá.

`eshop/.env` je jen na serveru (není v gitu): `APP_KEY`, `APP_ENV=production`, `APP_DEBUG=false`,
`APP_URL=https://vb-porcelan.webtitan.cz`, `MAIL_MAILER=resend`, `RESEND_API_KEY`, `INQUIRY_TO`, `ADMIN_*`.
