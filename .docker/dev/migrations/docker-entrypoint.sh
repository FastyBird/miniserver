#!/bin/sh
set -e

attempt_left=20

until php vendor/bin/fb-console dbal:run-sql "select 1" >/dev/null 2>&1;
do
		attempt_left=$((attempt_left-1))

		if [ "${attempt_left}" -eq "0" ]; then

				(>&2 echo "MySQL did not answer. Aborting migrations.")

				exec 1
		else
				(>&2 echo "Waiting for MySQL to be ready...")
		fi

		sleep 1
done

php vendor/bin/fb-console migrations:migrate --no-interaction

if [ "$LOAD_FIXTURES" = "1" ]; then
		php bin/console doctrine:fixtures:load --no-interaction
fi
