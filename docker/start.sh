#!/bin/sh
# Container entrypoint: prepares Apache + database, then serves the app.
# Kept resilient so a transient DB error never silently skips migrations.

PORT="${PORT:-80}"

sed -i "s/Listen 80/Listen ${PORT}/" /etc/apache2/ports.conf
sed -i "s/:80/:${PORT}/" /etc/apache2/sites-available/000-default.conf

php artisan config:clear || true

echo "[start] running migrations..."
attempt=0
until php artisan migrate --force; do
    attempt=$((attempt + 1))
    if [ "$attempt" -ge 5 ]; then
        echo "[start] ERROR: migrations failed after ${attempt} attempts"
        break
    fi
    echo "[start] migration attempt ${attempt} failed; retrying in 5s..."
    sleep 5
done

php artisan migrate:status || true

echo "[start] seeding database..."
php artisan db:seed --force || echo "[start] WARNING: seed failed (non-fatal)"

echo "[start] linking storage..."
php artisan storage:link --force || true

# Keep runtime-writable dirs owned by the web user.
chown -R www-data:www-data storage bootstrap/cache || true

echo "[start] starting apache on port ${PORT}"
exec apache2-foreground
