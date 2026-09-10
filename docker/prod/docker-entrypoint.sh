#!/bin/sh
#
# FastyBird MiniServer - production entrypoint
#
set -e

prepare_dirs() {
	mkdir -p "${FB_LOGS_DIR:-/app/var/logs}" "${FB_TEMP_DIR:-/app/var/temp}"
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
		(>&2 echo "Database did not answer. Aborting migrations wait.")
		break
	else
		(>&2 echo "Waiting for the database to be ready...")
	fi

	sleep 1
done

if [ "${attempt_left}" != "0" ]; then
	echo "Database is reachable."
	echo "Schema creation is a manual step until Phase 3 adds migrations:"
	echo "  docker exec <container> php bin/fb-console.php orm:schema-tool:create"
fi

# Again, because the wait loop's console just ran as root and re-created
# var/temp/cache/* (and supervisord will create var/logs/*.log) owned by
# root; this second pass is the one that actually sticks before php-fpm
# and the www-data-run supervisor programs start.
prepare_dirs

exec /usr/bin/supervisord -c /etc/supervisor/supervisord.conf
