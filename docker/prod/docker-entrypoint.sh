#!/bin/sh
#
# FastyBird MiniServer - production entrypoint
#
set -e

mkdir -p "${FB_LOGS_DIR:-/app/var/logs}" "${FB_TEMP_DIR:-/app/var/temp}"
chown -R www-data:www-data "${FB_LOGS_DIR:-/app/var/logs}" "${FB_TEMP_DIR:-/app/var/temp}"

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

exec /usr/bin/supervisord -c /etc/supervisor/supervisord.conf
