#!/bin/sh
set -e

PORT="${PORT:-3000}"

printf 'Listen %s\n' "$PORT" > /etc/apache2/ports.conf
sed -ri "s|<VirtualHost \*:80>|<VirtualHost \*:${PORT}>|g" /etc/apache2/sites-available/000-default.conf

exec "$@"
