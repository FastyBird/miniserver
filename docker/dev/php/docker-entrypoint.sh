#!/bin/sh
#
# FastyBird MiniServer - development PHP entrypoint
#
# Wraps the image's own entrypoint for the dev PHP services (application,
# ws-server, devices-module, migrations). It does one thing: let them start
# on a checkout that has no frontend build.
#
# contributte/vite resolves the manifest while the DI container is compiled,
# and throws when it finds no manifest.json under public/. Without a
# `pnpm build` every console command and every HTTP request then fails. This
# script does not write a manifest into public/: a leftover one there once
# made a test pass locally and fail in CI. When there is no build, it points
# FB_CONFIG_DIR at no-frontend-build/ instead. That directory's common.neon
# sets contributteVite.manifestFile to a placeholder that lives next to it,
# and its local.neon is a link to config/local.neon, so your own overrides
# still load. The application then loads:
#
#   config/common.neon, config/defaults.neon     (as always)
#   docker/dev/php/no-frontend-build/common.neon (the placeholder manifest)
#   config/local.neon, through the link          (if it exists)
#
# After a `pnpm build`, public/ holds the real manifest and the services use
# it as soon as they are restarted. The two cases load different
# configuration files, so they compile different DI containers and a cached
# container of one is never reused for the other.
#
# Only stderr is written to: nothing may reach stdout before the container is
# initialised.
#
set -e

if [ -z "$(find /app/public -name manifest.json -type f 2>/dev/null)" ]; then
	(>&2 echo "No frontend build in public/: using the dev placeholder Vite manifest (docker/dev/php/no-frontend-build/).")

	export FB_CONFIG_DIR=/app/docker/dev/php/no-frontend-build
fi

exec docker-php-entrypoint "$@"
