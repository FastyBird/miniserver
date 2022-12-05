#!/bin/sh
set -e

readonly timeout=100
readonly sleep_time=5

i=1
time=$((timeout * sleep_time))

until curl -L --fail http://localhost:80 2>/dev/null
do
    i=$((i+1))

    if [ "${i}" -gt "${timeout}" ]; then

        echo "FastyBird MiniServer was never created, aborting due to ${time}s timeout!"
        curl -L http://localhost:80 -H Accept:text/html
        exit 1
    else
        echo "FastyBird MiniServer did not response"
    fi

    sleep $sleep_time
done
