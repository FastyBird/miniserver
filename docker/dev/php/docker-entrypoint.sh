#!/bin/sh
#
# FastyBird MiniServer - development PHP entrypoint
#
# Wraps the image's own entrypoint for the dev PHP services (application,
# ws-server, devices-module, migrations). It lets them start on a checkout that
# has no frontend build.
#
# contributte/vite resolves the manifest while the DI container is compiled,
# and throws when it finds no manifest.json under public/. Without a
# `pnpm build`, every console command and every HTTP request then fails. This
# script does not write a manifest into public/: a leftover one there once made
# a test pass locally and fail in CI.
#
# Instead, the dev compose file sets FB_CONFIG_DIR to the directory below, and
# this script fills it with links each time the container starts:
#
#   local.neon  -> config/local.neon, your own overrides (skipped if absent)
#   common.neon -> docker/dev/php/no-frontend-build/common.neon, the placeholder
#                  manifest; only when public/ holds no manifest.json
#
# The directory is inside the container, not in the bind-mounted checkout, so
# `docker compose exec` sees the same choice the service made when it started.
# After a `pnpm build`, restart the PHP services and they use the real
# manifest. The two cases load different configuration files, so they compile
# different DI containers and a cached container of one is never reused for the
# other.
#
# Only stderr is written to: nothing may reach stdout before the container is
# initialised.
#
set -e

# Must match FB_CONFIG_DIR in docker/dev/docker-compose.yml
dev_config_dir=/tmp/fastybird-dev-config

mkdir -p "${dev_config_dir}"

ln -sfn /app/config/local.neon "${dev_config_dir}/local.neon"

if [ -z "$(find /app/public -name manifest.json -type f 2>/dev/null)" ]; then
	ln -sfn /app/docker/dev/php/no-frontend-build/common.neon "${dev_config_dir}/common.neon"

	(>&2 echo "No frontend build in public/: using the dev placeholder Vite manifest (docker/dev/php/no-frontend-build/).")
else
	rm -f "${dev_config_dir}/common.neon"
fi

# Make var/temp and var/logs writable by every PHP process of the stack. The services run as
# root (console commands, migrations) and php-fpm's workers run as www-data, and they share the
# bind-mounted checkout. On a Linux host the checkout belongs to the host user, so without this
# the first process to compile the DI container (the migrations service, as root) leaves
# var/temp/cache owned by root, and the first HTTP request answers 500 with "Unable to create
# file .../var/temp/cache/fb.di.configurator/Container_*.php.lock: Permission denied". Docker
# Desktop maps all ownership to the host user and so never showed it. Both directories only hold
# caches and logs, are git-ignored, and this is the development stack. The umask makes what the
# processes create from here on writable by the others too.
mkdir -p /app/var/temp /app/var/logs
chmod -R a+rwX /app/var/temp /app/var/logs 2>/dev/null || true
umask 000

export FB_CONFIG_DIR="${dev_config_dir}"

exec docker-php-entrypoint "$@"
