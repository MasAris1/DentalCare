#!/bin/sh
set -e

APP_UID="${APP_UID:-1000}"
APP_GID="${APP_GID:-1000}"

sed -ri "s/^user = .*/user = ${APP_UID}/" /usr/local/etc/php-fpm.d/www.conf
sed -ri "s/^group = .*/group = ${APP_GID}/" /usr/local/etc/php-fpm.d/www.conf

mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache
chown -R "${APP_UID}:${APP_GID}" storage bootstrap/cache

exec "$@"
