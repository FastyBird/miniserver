#!/bin/sh
set -e

# first arg is `-f` or `--some-option`
if [ "${1#-}" != "$1" ]; then
	set -- node "$@"
fi

if [ "$1" = 'node' ] || [ "$1" = 'yarn' ]; then
	>&2 echo "Waiting for backend to be ready..."
	until nc -z "$BACKEND_HOST" "$BACKEND_PORT"; do
		sleep 1
	done
fi

exec "$@"
