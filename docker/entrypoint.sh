#!/bin/sh
set -eu

port="${PORT:-10000}"
case "$port" in
    ""|*[!0-9]*) echo "PORT must be numeric" >&2; exit 1 ;;
esac

sed "s/__PORT__/${port}/g" /etc/nginx/templates/default.conf.template > /etc/nginx/conf.d/default.conf
mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

# Route caching is intentionally omitted because the application keeps a small
# closure route for the client profile endpoint.
php artisan config:cache
php artisan view:cache

# Render Free has no pre-deploy command. Enable this explicitly for a planned
# schema deployment; leave it false for ordinary restarts.
if [ "${RUN_MIGRATIONS:-false}" = "true" ]; then
    php artisan migrate --force
fi

php-fpm -F &
php_pid=$!
nginx -g 'daemon off;' &
nginx_pid=$!
cleanup() {
    kill -TERM "$nginx_pid" "$php_pid" 2>/dev/null || true
}
trap cleanup TERM INT
wait "$nginx_pid"
status=$?
cleanup
wait "$php_pid" 2>/dev/null || true
exit "$status"