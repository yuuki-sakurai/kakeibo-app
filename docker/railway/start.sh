#!/usr/bin/env bash
set -Eeuo pipefail
cd /var/www/html

# Allow one-off commands such as migrations with the same image.
if (( $# > 0 )); then
    exec "$@"
fi

: "${APP_KEY:?Set a persistent APP_KEY in Railway Variables before deployment}"
export PORT="${PORT:-8080}"
if [[ ! "$PORT" =~ ^[0-9]{1,5}$ ]] || (( 10#$PORT < 1 || 10#$PORT > 65535 )); then
    echo "PORT must be an integer between 1 and 65535" >&2
    exit 1
fi

mkdir -p storage/framework/{cache/data,sessions,views} storage/logs bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

# Build caches after Railway injects runtime variables. Do not flush DB caches.
php artisan config:cache
php artisan route:cache
chown -R www-data:www-data bootstrap/cache

# Restrict substitution so nginx variables such as $document_root survive.
envsubst '${PORT}' < docker/railway/nginx.conf.template > /etc/nginx/nginx.conf
nginx -t
php-fpm -t

php_pid=''
nginx_pid=''
shutdown() {
    trap '' TERM INT
    [[ -z "$nginx_pid" ]] || kill -QUIT "$nginx_pid" 2>/dev/null || true
    [[ -z "$php_pid" ]] || kill -QUIT "$php_pid" 2>/dev/null || true
    wait || true
}
trap 'shutdown; exit 0' TERM INT

php-fpm --nodaemonize &
php_pid=$!
nginx -g 'daemon off;' &
nginx_pid=$!

# If either server stops, stop its peer and let Railway restart the container.
status=0
wait -n "$php_pid" "$nginx_pid" || status=$?
shutdown
if (( status == 0 )); then
    status=1
fi
exit "$status"
