#!/bin/sh
#
# FastyBird MiniServer - production entrypoint
#
set -e

# Fail loudly before anything else starts if the JWT signing key is
# missing. The bootstrap does not filter empty parameter values and static
# parameters win over config defaults, so an unset/empty signature reaches
# the JWT library as '' and it throws on every sign-in and authenticated
# request — while php-fpm, nginx and every supervisor program still come up
# clean and the exception log stays empty. That failure mode is silent and
# indistinguishable from a healthy container, which is worse than refusing
# to boot. A signing key must never have a built-in default (in particular
# never the value tracked in this repository's .env / var/config/
# defaults.neon, which is published and public), so this is a hard stop,
# not a fallback.
check_security_signature() {
	if [ -z "${FB_APP_PARAMETER__SECURITY_SIGNATURE:-}" ]; then
		(>&2 cat <<'EOF'
FATAL: FB_APP_PARAMETER__SECURITY_SIGNATURE is not set (or empty).

This is the JWT signing key used to sign and verify every access token.
Without it, authentication cannot work: sign-in and every authenticated
request will throw. The container will not start with broken auth.

Generate one and supply it to this container, e.g.:

  openssl rand -base64 32

Then set FB_APP_PARAMETER__SECURITY_SIGNATURE to that value in the
environment (for docker compose, put it in docker/prod/.env, which is
not tracked in this repository).
EOF
		)
		exit 1
	fi
}

check_security_signature

prepare_dirs() {
	mkdir -p "${FB_LOGS_DIR:-/app/var/logs}" "${FB_TEMP_DIR:-/app/var/temp}"

	# The compiled DI container lives under var/temp/cache with auto-rebuild
	# disabled in production, and its cache key does not cover config file
	# *contents*. /app/var is a volume that survives upgrades, so on a
	# code-only redeploy the previous release's compiled container would
	# otherwise be reloaded against the new code. Purging it on every start
	# is deliberate and cheap (Nette just recompiles on first request) — do
	# not "optimise" this away to save a rebuild.
	rm -rf "${FB_TEMP_DIR:-/app/var/temp}/cache"

	mkdir -p "${FB_TEMP_DIR:-/app/var/temp}"
	chown -R www-data:www-data "${FB_LOGS_DIR:-/app/var/logs}" "${FB_TEMP_DIR:-/app/var/temp}"
}

# Before the wait loop, so the (root-run) console below can write to a
# brand-new volume at all.
prepare_dirs

attempt_left=20

until php bin/fb-console.php dbal:run-sql "select 1" >/dev/null 2>&1;
do
	attempt_left=$((attempt_left-1))

	if [ "${attempt_left}" -eq "0" ]; then
		(>&2 echo "FATAL: database did not answer after 20 attempts. Aborting startup.")
		exit 1
	else
		(>&2 echo "Waiting for the database to be ready...")
	fi

	sleep 1
done

echo "Database is reachable."
echo "Schema creation is a manual step until Phase 3 adds migrations:"
echo "  docker exec <container> php bin/fb-console.php orm:schema-tool:create"

# Again, because the wait loop's console just ran as root and re-created
# var/temp/cache/* (and supervisord will create var/logs/*.log) owned by
# root; this second pass is the one that actually sticks before php-fpm
# and the www-data-run supervisor programs start.
prepare_dirs

exec /usr/bin/supervisord -c /etc/supervisor/supervisord.conf
