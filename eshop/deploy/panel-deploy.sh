# Skript nasazení pro hosting-panel (panel.webtitan.cz → vbporcelan → Deployment).
# Panel naklonuje repozitář Sporda/vb-porcelan do public_html a tento skript pustí jako uživatel webu.
# Kořen webu je public_html/eshop/public. Nic se nesestavuje (CSS a JS jsou hotové soubory v public/assets).
set -e
cd eshop
composer install --no-dev --optimize-autoloader --no-interaction
# Při úplně prvním nasazení ještě neexistuje .env (vytváří se na serveru ručně, není v gitu).
if [ ! -f .env ]; then echo "eshop/.env chybí – migrace a cache přeskočeny. Vytvořte .env a spusťte nasazení znovu."; exit 0; fi
touch database/database.sqlite
# Starou cache konfigurace pryč, ať migrace a seedery čtou aktuální .env
php artisan config:clear
php artisan migrate --force
# Katalog a první účet administrace (seedery jsou idempotentní – nic nepřepíšou, co už existuje)
php artisan db:seed --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
