#!/usr/bin/env bash
# E7.0 census (#694): run one PHP census script in the application image, exactly as every
# number in docs/superpowers/plans/2026-10-10-e7-census.md was produced.
#
#   tools/census/e7/php.sh tools/census/e7/census.php <table> [--package <Type>[/<Name>]]...
#
# The repository is mounted read-only at /app and the scratch directory E7_DIR (default
# /tmp/e7-census) at /e7, where the scripts cache their parse index and where the phpcs, Rector
# and DI-snapshot inputs of T8/T9 are written. The test ini is on the scan dir, as `make tests`
# puts it, so vendor's PHP 8.4 deprecations do not print to stdout. The scripts themselves refuse
# to run on a symlinked or stale vendor/fastybird/* mirror (lib.php); the prerequisite is
# (CLAUDE.md traps):
#   rm -rf vendor/fastybird && COMPOSER_MIRROR_PATH_REPOS=1 composer install   (in the image)
#   find vendor/fastybird -maxdepth 1 -type l                                   (prints nothing)
set -euo pipefail
root="$(cd "$(dirname "$0")/../../.." && pwd)"
scratch="${E7_DIR:-/tmp/e7-census}"
mkdir -p "$scratch"
exec docker run --rm -v "$root":/app:ro -v "$scratch":/e7 -w /app \
	-e XDEBUG_MODE=off -e TZ=UTC -e PHP_DATE_TIMEZONE=UTC -e PHP_INI_SCAN_DIR=:/app/tools/php.d \
	-e E7_DIR=/e7 \
	"${E7_IMAGE:-fb-e2-app:latest}" php -d memory_limit=4G "$@"
