#!/bin/sh
#
# FastyBird MiniServer - development migrations entrypoint
#
# Waits for the database to answer, then migrates it to the latest version
# in migrations/, as the production entrypoint does.
#
set -e

attempt_left=20

# The probe boots the whole application, so it fails for more reasons than an
# unreachable database: a DI container that does not compile fails it too.
# Its output is kept, and the last attempt's is printed before aborting.
until probe_output=$(php bin/fb-console.php dbal:run-sql "select 1" 2>&1);
do
	attempt_left=$((attempt_left-1))

	if [ "${attempt_left}" -eq "0" ]; then
		(>&2 echo "Database did not answer. Aborting. The last attempt's output:")
		(>&2 printf '%s\n' "${probe_output}")
		exit 1
	else
		(>&2 echo "Waiting for the database to be ready...")
	fi

	sleep 1
done

echo "Database is reachable."

php bin/fb-console.php no:such:command
