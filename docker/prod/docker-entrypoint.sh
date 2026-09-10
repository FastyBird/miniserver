#!/bin/sh
#
# FastyBird MiniServer - production entrypoint
#
set -e

CONFIG_DIR="${FB_CONFIG_DIR:-/data/config}"
LOGS_DIR="${FB_LOGS_DIR:-/data/logs}"
TEMP_DIR="${FB_TEMP_DIR:-/data/temp}"
LOCAL_NEON="${CONFIG_DIR}/local.neon"

# Never boot with dead authentication.
#
# Background: the previous revision of this entrypoint refused to start
# whenever FB_APP_PARAMETER__SECURITY_SIGNATURE was unset or empty. That
# was a direct response to a real incident: the bootstrap does not filter
# empty parameter values and a static parameter (an env var) always wins
# over a config file's value, so an unset/empty signing key reached the
# JWT library as '' and it threw on every sign-in and every authenticated
# request — while php-fpm, nginx and every supervisor program still came
# up clean and the exception log stayed empty. Refusing to boot turned
# that silent failure into a loud one.
#
# FB_CONFIG_DIR (/data/config) now lives inside the declared /data volume
# and survives container replacement, so a better fix exists: generate a
# signature on first start and persist it to local.neon. A fresh install
# then works immediately and each install gets a unique key, instead of
# every operator being forced to supply one by hand.
#
# The invariant this function preserves is "never boot with dead
# authentication", not "always refuse" — so a failure to obtain a
# signature by either path (operator-supplied or generated) must still be
# fatal, exactly like the old hard requirement was.
ensure_security_signature() {
	if [ -n "${FB_APP_PARAMETER__SECURITY_SIGNATURE:-}" ]; then
		# Operator-supplied signature wins, same as before. Nothing to do.
		return 0
	fi

	# docker/prod/docker-compose.yml always sets this key (it has no ":-"
	# fallback of its own), so an operator who has not supplied
	# SECURITY_SIGNATURE reaches this entrypoint with the variable present
	# but EMPTY, not absent. Because a static parameter wins over anything
	# written to local.neon below, leaving it set-but-empty would silently
	# reintroduce the exact dead-authentication defect this function
	# exists to prevent — unset it so config-file resolution (local.neon)
	# applies instead.
	unset FB_APP_PARAMETER__SECURITY_SIGNATURE

	if ! mkdir -p "${CONFIG_DIR}"; then
		(>&2 echo "FATAL: could not create ${CONFIG_DIR} to store the security signature.")
		exit 1
	fi

	if [ -f "${LOCAL_NEON}" ] && grep -q 'signature:' "${LOCAL_NEON}"; then
		# Generated on a previous start of this volume (or hand-edited by
		# the operator directly into local.neon). Nothing to do.
		return 0
	fi

	# -d date.timezone=UTC avoids a PHP Startup warning ("Invalid
	# date.timezone value") that this image's php CLI otherwise writes to
	# stdout (not stderr) when no ini timezone is configured — and stdout
	# is exactly what this command substitution captures. Belt and braces:
	# the case check below also rejects anything that is not pure base64,
	# so any stray output from php (a warning, a notice, anything) is
	# caught and refused rather than silently written into local.neon.
	SIGNATURE=$(php -d date.timezone=UTC -r 'echo base64_encode(random_bytes(32));' 2>/dev/null || true)

	case "${SIGNATURE}" in
		'')
			(>&2 echo "FATAL: could not generate a security signature (php random_bytes failed or produced no output).")
			exit 1
			;;
		*[!A-Za-z0-9+/=]*)
			(>&2 echo "FATAL: php emitted unexpected output while generating the security signature; refusing to persist a possibly corrupted value.")
			exit 1
			;;
	esac

	if [ ! -f "${LOCAL_NEON}" ]; then
		printf 'parameters:\n' > "${LOCAL_NEON}"
	fi

	{
		printf '    security:\n'
		printf "        signature: '%s'\n" "${SIGNATURE}"
	} >> "${LOCAL_NEON}"

	# Verify the write actually landed before trusting it. Never proceed
	# on a silent write failure (e.g. a volume that is mounted read-only)
	# — that is precisely the "container looks healthy, auth is dead"
	# failure mode this function exists to prevent.
	if [ ! -s "${LOCAL_NEON}" ] || ! grep -qF "signature: '${SIGNATURE}'" "${LOCAL_NEON}"; then
		(>&2 echo "FATAL: failed to persist the generated security signature to ${LOCAL_NEON}.")
		exit 1
	fi

	(>&2 echo "Generated a new security signature into ${LOCAL_NEON}")
}

ensure_security_signature

prepare_dirs() {
	mkdir -p "${LOGS_DIR}" "${TEMP_DIR}"

	# The compiled DI container lives under temp/cache with auto-rebuild
	# disabled in production, and its cache key does not cover config file
	# *contents*. /data is a volume that survives upgrades, so on a
	# code-only redeploy the previous release's compiled container would
	# otherwise be reloaded against the new code. Purging it on every start
	# is deliberate and cheap (Nette just recompiles on first request) — do
	# not "optimise" this away to save a rebuild.
	rm -rf "${TEMP_DIR}/cache"

	mkdir -p "${TEMP_DIR}"
	chown -R www-data:www-data "${LOGS_DIR}" "${TEMP_DIR}"
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

php bin/fb-console.php migrations:migrate --no-interaction --allow-no-migration

# Again, because the wait loop's console just ran as root and re-created
# /data/temp/cache/* (and supervisord will create /data/logs/*.log) owned by
# root; this second pass is the one that actually sticks before php-fpm
# and the www-data-run supervisor programs start.
prepare_dirs

exec /usr/bin/supervisord -c /etc/supervisor/supervisord.conf
