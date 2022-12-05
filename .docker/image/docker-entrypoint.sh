#!/bin/sh
set -e

mkdir -p var/temp var/logs

composer dump-autoload --classmap-authoritative;

exec /usr/bin/supervisord -c /etc/supervisor/supervisord.conf
