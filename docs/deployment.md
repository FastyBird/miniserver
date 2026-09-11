# Deployment

## Docker images

`docker/dev/` holds the development Compose file and Dockerfiles (`docker/dev/{nginx,node,php}`); `docker/prod/Dockerfile` builds the production image in three stages: `ui` (`node:24`, `yarn install --frozen-lockfile && yarn build`), `vendor` (the `composer:2` image, `composer install --no-dev --prefer-dist --no-interaction --classmap-authoritative --ignore-platform-reqs` with `COMPOSER_MIRROR_PATH_REPOS=1` -- `--ignore-platform-reqs` is there because the `composer:2` image's own bundled PHP is not the frozen 8.2 runtime and lacks extensions this project needs; the runtime stage below installs the real extension set, so the check is redundant at this stage, not skipped for real), and `runtime` (`php:8.2-fpm` with nginx and supervisor, the PHP extensions `bcmath`, `gd`, `gmp`, `intl`, `pcntl`, `pdo_mysql`, `sockets`, `zip`, `opcache`).

The production image is published to `ghcr.io/fastybird/miniserver`, tagged `latest` on `main` and with the release version on tags (see `.github/workflows/release.yml`). `docker/prod/docker-compose.yml` currently builds this image locally (`docker compose -f docker/prod/docker-compose.yml build`) rather than pulling it -- CI builds the image (`push: false`) but nothing publishes it yet, so `docker compose pull` for the `application` service fails until a later phase adds that.

### Production defaults

| Variable | Default |
|---|---|
| `FB_CONFIG_DIR` | `/data/config` |
| `FB_LOGS_DIR` | `/data/logs` |
| `FB_TEMP_DIR` | `/data/temp` |

All three live under the single declared volume `/data`. The container exposes port `80` (HTTP) and `8888` (WebSocket).

**Healthcheck is `GET /favicon.ico`, not `GET /`.** The Nette application has no registered router (see [architecture.md](./architecture.md#request-routing)), so `/` 404s on every request; a healthcheck against it would report the container permanently unhealthy. `/favicon.ico` is served directly by nginx from `public/` and genuinely returns 200. Do not "fix" the healthcheck target back to `/` -- that was tried, and it was wrong.

**A security signature is required.** The entrypoint (`docker/prod/docker-entrypoint.sh`) generates a random one into `config/local.neon` on first start if neither that file nor `FB_APP_PARAMETER__SECURITY_SIGNATURE` already supplies one, and refuses to boot (fatal, loud) if it cannot obtain or persist one -- the previous behaviour of silently signing every token with an empty string, which authenticated nothing, is exactly the failure this exists to prevent. The generated value is written into the `/data` volume, so it survives container replacement but is tied to that volume. For any deployment with more than one application replica, or any deployment where you need to reproduce the same signature after recreating the volume, set `SECURITY_SIGNATURE` explicitly (`docker/prod/docker-compose.yml` reads it with no fallback default, deliberately -- the value tracked in this public repository's own `.env`/`config/defaults.neon` must never reach production). **Losing the signature invalidates every token issued under it** -- every signed-in session and every API key that relies on it stops validating.

The entrypoint then waits for the database to answer, runs `php bin/fb-console.php migrations:migrate --no-interaction --allow-no-migration`, then execs supervisord with the programs below.

## Runtime processes

HTTP is served by nginx in front of php-fpm; every request hits `public/index.php`. `docker/prod/supervisor/supervisord.conf` bakes in exactly five programs -- confirmed by `.github/workflows/ci-tests.yaml`'s "all five supervisor programs are RUNNING" smoke-test step:

- `php-fpm`
- `nginx`
- `ws-server` -- `php /app/bin/fb-console.php fb:ws-server:start`, the WebSocket server, port `8888`
- `exchange` -- `php /app/bin/fb-console.php fb:devices-module:exchange -n`, the devices-module message exchange consumer
- `subprocess-watcher` -- an `[eventlistener]`, `php /app/bin/fb-supervisor.php`, that stops supervisord when a program dies unexpectedly so the container's own restart policy takes over

### Per-connector processes are not wired up in production today

Each configured connector is meant to run as its own long-running process, `php /app/bin/fb-console.php fb:devices-module:connector <identifier> -n`, one per connector identifier. **There is currently no mechanism in the shipped production image that starts one automatically.** `docker/prod/supervisor/supervisord.conf` has no `[include]` directive, by design: an earlier version tried globbing `/data/config/supervisor/*.conf`, but this repository's own supervisor configs live one level deeper (under `config/supervisor/{plugins,system,modules}/`, ported from the pre-merge framework repository and not otherwise used by anything today), so the glob matched nothing -- and a *correct* glob would have picked up `config/supervisor/system/application.conf`, which runs `yarn workspace @fastybird/application dev`. That command doesn't exist in this image (no node/yarn in `runtime`), so it would go `FATAL` and, depending on `autorestart`, could take the whole container down; it would also start a second WebSocket server colliding with the baked-in `ws-server` program. `.gitignore`'s `/config/supervisor/*.local.conf` entry and the `config/supervisor/` tree itself are therefore currently vestigial, not a working drop-in mechanism.

To actually run a connector under supervisord in production today, add a `[program:fb.connector.<identifier>]` block directly to `docker/prod/supervisor/supervisord.conf` and rebuild the image. Use this template as the starting point for that block:

```ini
[program:fb.connector.<identifier>]
command = php /app/bin/fb-console.php fb:devices-module:connector <identifier> -n
process_name = %(program_name)s
numprocs = 1
autostart = true
autorestart = true
startsecs = 5
startretries = 3
redirect_stderr = true
stdout_logfile = /data/logs/%(program_name)s.log
stderr_logfile = /data/logs/%(program_name)s-error.log
```

Wiring an `[include]` glob that safely covers this case, so an operator can drop a config file into the `/data` volume without rebuilding the image, is unresolved -- a later phase should either fix the glob path and remove the dev-only `config/supervisor/system/application.conf` program from what it would pick up, or provide a purpose-built connector-programs directory.

## Development

```sh
docker compose -f docker/dev/docker-compose.yml up -d
```

Default services: `web-server` (nginx), `application` (php-fpm with xdebug), `ui-server` (node running `yarn dev`), `ws-server`, `devices-module` (exchange), `migrations` (one-shot) and `database` (MariaDB). `redis`, `couchdb`, `rabbitmq` and `mqtt` are behind Compose profiles -- see [configuration.md](./configuration.md) for which extension needs which profile. A LAN-facing macvlan network is available as an override: `docker compose -f docker/dev/docker-compose.yml -f docker/dev/docker-compose.lan.yml up -d`.

## Debian packaging (unsupported)

`build/debian/` carries the old miniserver repository's `control`, `postinst`, `prerm`, `fb-miniserver.service` and `make_deb.sh`, ported as a starting point for a later appliance-style install path. It is not validated against the current PHP 8.2 / MariaDB stack and should not be used to deploy this application today -- use Docker.
