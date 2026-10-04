#!/usr/bin/env bash
# E5.1 census (#633): run one PHP census script in the application image, exactly as every
# number in docs/superpowers/plans/2026-10-04-core-e5-census.md was produced.
#
#   tools/census/e5/php.sh <script.php> [args...]     (stdout = the script's output)
#
# The repository is mounted read-only at /app, the scratch directory E633_DIR (default
# /tmp/e633) at /e633. The test ini is on the scan dir, as `make tests` puts it, so vendor's
# PHP 8.4 deprecations do not print to stdout. Prerequisite (CLAUDE.md traps):
#   rm -rf vendor/fastybird && COMPOSER_MIRROR_PATH_REPOS=1 composer install   (in the image)
#   find vendor/fastybird -maxdepth 1 -type l                                   (prints nothing)
set -euo pipefail
root="$(cd "$(dirname "$0")/../../.." && pwd)"
scratch="${E633_DIR:-/tmp/e633}"
mkdir -p "$scratch"
exec docker run --rm -v "$root":/app:ro -v "$scratch":/e633 -w /app \
	-e XDEBUG_MODE=off -e TZ=UTC -e PHP_DATE_TIMEZONE=UTC -e PHP_INI_SCAN_DIR=:/app/tools/php.d \
	"${E633_IMAGE:-fb-e2-app:latest}" php -d memory_limit=4G "$@"
