# Deployment

## Docker images

`docker/dev/` holds the development Compose file and Dockerfiles (`docker/dev/{nginx,node,php}`); `docker/prod/Dockerfile` builds the production image in three stages: `ui` (`node:24`, `pnpm install --frozen-lockfile && pnpm build`), `vendor` (the `composer:2` image, `composer install --no-dev --prefer-dist --no-interaction --classmap-authoritative --ignore-platform-reqs` with `COMPOSER_MIRROR_PATH_REPOS=1` -- `--ignore-platform-reqs` is there because the `composer:2` image's own bundled PHP is not the frozen 8.4 runtime and lacks extensions this project needs; the runtime stage below installs the real extension set, so the check is redundant at this stage, not skipped for real), and `runtime` (`php:8.4-fpm` with nginx and supervisor, the PHP extensions `bcmath`, `gd`, `gmp`, `intl`, `pcntl`, `pdo_mysql`, `sockets`, `zip`, `opcache`).

The production image is published to `ghcr.io/fastybird/miniserver`, tagged `latest` on `main` and with the release version on tags (see `.github/workflows/release.yml`). `docker/prod/docker-compose.yml` currently builds this image locally (`docker compose -f docker/prod/docker-compose.yml build`) rather than pulling it -- CI builds the image (`push: false`) but nothing publishes it yet, so `docker compose pull` for the `application` service fails until a later phase adds that.

### Production defaults

| Variable | Default |
|---|---|
| `FB_CONFIG_DIR` | `/data/config` |
| `FB_LOGS_DIR` | `/data/logs` |
| `FB_TEMP_DIR` | `/data/temp` |

All three live under the single declared volume `/data`. The container exposes port `80` (HTTP) and `8888` (WebSocket).

**Healthcheck is `GET /favicon.ico`, not `GET /`.** The Nette application routes `/` to `App:Default:default`, but the presenter looks its template up as `Default.latte` and the file is `default.latte`, so on the image's case-sensitive filesystem `/` ends in a 404 (see [architecture.md](./architecture.md#request-routing)); a healthcheck against it would report the container permanently unhealthy. `/favicon.ico` is served directly by nginx from `public/` and genuinely returns 200. Do not "fix" the healthcheck target back to `/` -- that was tried, and it was wrong.

**A security signature is required.** The entrypoint (`docker/prod/docker-entrypoint.sh`) generates a random one into `config/local.neon` on first start if neither that file nor `FB_APP_PARAMETER__SECURITY_SIGNATURE` already supplies one, and refuses to boot (fatal, loud) if it cannot obtain or persist one -- the previous behaviour of silently signing every token with an empty string, which authenticated nothing, is exactly the failure this exists to prevent. The generated value is written into the `/data` volume, so it survives container replacement but is tied to that volume. For any deployment with more than one application replica, or any deployment where you need to reproduce the same signature after recreating the volume, set `SECURITY_SIGNATURE` explicitly (`docker/prod/docker-compose.yml` reads it with no fallback default, deliberately -- the value tracked in this public repository's own `.env`/`config/defaults.neon` must never reach production). **Losing the signature invalidates every token issued under it** -- every signed-in session and every API key that relies on it stops validating.

The entrypoint then waits for the database to answer, runs `php bin/fb-console.php migrations:migrate --no-interaction --allow-no-migration`, then execs supervisord with the programs below. The wait probe, `dbal:run-sql "select 1"`, boots the whole application, so a container that does not compile fails it as well as an unreachable database does. After 20 failed attempts the entrypoint aborts and prints the last attempt's output, which names the real cause.

### Databases created before the migration baseline

`migrations/` holds one version, `Version20261011210849`: a baseline of the whole schema, in utf8mb4 with `utf8mb4_general_ci` on every table. It replaced the three earlier versions (`Version20260910150558`, `Version20260913085735` and `Version20260917084547`). On a database migrated with those, `migrations:migrate` fails with `Base table or view already exists`, so the entrypoint above stops. Fix such a database in one of two ways:

- **Recreate it** (recommended): drop and recreate the database, empty. The next start migrates it from the baseline.
- **Keep it**: replace the old versions with the baseline in the version table, then move the three join tables that were created in utf8mb3 to utf8mb4:

  ```sh
  php bin/fb-console.php migrations:version --delete --all --no-interaction
  php bin/fb-console.php migrations:version --add --all --no-interaction
  ```

  ```sql
  ALTER TABLE fb_devices_module_devices_children CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
  ALTER TABLE fb_ui_module_widgets_groups CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
  ALTER TABLE fb_ui_module_widgets_tabs CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
  ```

  The database then differs from a fresh one only in the display width of its boolean columns (`tinyint(1)` instead of `tinyint`), which Doctrine treats as equal.

## Runtime processes

HTTP is served by nginx in front of php-fpm; every request hits `public/index.php`. `docker/prod/supervisor/supervisord.conf` bakes in exactly five programs -- confirmed by `.github/workflows/ci-tests.yaml`'s "all five supervisor programs are RUNNING" smoke-test step:

- `php-fpm`
- `nginx`
- `ws-server` -- `php /app/bin/fb-console.php fb:ws-server:start`, the WebSocket server, port `8888`
- `exchange` -- `php /app/bin/fb-console.php fb:devices-module:exchange -n`, the devices-module message exchange consumer
- `subprocess-watcher` -- an `[eventlistener]`, `php /app/bin/fb-supervisor.php`, that stops supervisord when a program dies unexpectedly so the container's own restart policy takes over

### Per-connector processes are not wired up in production today

Each configured connector is meant to run as its own long-running process, `php /app/bin/fb-console.php fb:devices-module:connector <identifier> -n`, one per connector identifier. **There is currently no mechanism in the shipped production image that starts one automatically.** `docker/prod/supervisor/supervisord.conf` has no `[include]` directive, by design: an earlier version tried globbing `/data/config/supervisor/*.conf`, but this repository's own supervisor configs live one level deeper (under `config/supervisor/{plugins,system,modules}/`, ported from the pre-merge framework repository and not otherwise used by anything today), so the glob matched nothing. `config/supervisor/system/application.conf` -- a dead program definition running a command that could not work in any package manager, `<pkg-manager> workspace @fastybird/application dev`, since `@fastybird/application`'s `package.json` has no `scripts` block -- was deleted in the Phase 6 pnpm migration rather than fixed, since a correct glob would otherwise have picked it up, taken the whole container down (`autorestart` turning a `FATAL` into a crash loop) and started a second WebSocket server colliding with the baked-in `ws-server` program. `.gitignore`'s `/config/supervisor/*.local.conf` entry and the rest of the `config/supervisor/` tree remain vestigial, not a working drop-in mechanism.

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

Prerequisites: Docker with Compose v2, and a checkout of this repository. Nothing else is needed on the host: PHP, Composer, Node and pnpm run in the stack's own images. Run every command from the repository root:

```sh
# PHP dependencies. COMPOSER_MIRROR_PATH_REPOS=1 copies the extensions into vendor/
# instead of symlinking them, as CI and the production image do.
docker compose -f docker/dev/docker-compose.yml run --rm --no-deps \
    -e XDEBUG_MODE=off -e COMPOSER_MIRROR_PATH_REPOS=1 \
    application composer install --no-interaction

# JS dependencies, for the ui-server (the Vite dev server).
docker compose -f docker/dev/docker-compose.yml run --rm --no-deps ui-server pnpm install --frozen-lockfile

# Start the stack.
docker compose -f docker/dev/docker-compose.yml up -d
```

The PHP image copies `docker/dev/php/conf/php.ini` in when it is built, so an image built before a change to that file keeps the old settings. Rebuild it with `docker compose -f docker/dev/docker-compose.yml build`, then `up -d` again. In particular, the dev image now turns `display_errors` off, as the production image does: with it on, PHP 8.4 deprecations from vendor code are printed before any header and every HTTP request answers with a Tracy "headers already sent" page instead of its real response. An image built before this change does exactly that until it is rebuilt.

After an edit under `src/FastyBird/<Type>/<Name>/src/`, refresh that package's copy with `composer reinstall <package>`, run the same way (see the Traps in `CLAUDE.md`).

On a fresh checkout the stack then does the rest on its own:

- **Ports.** `MYSQL_PORT` sets only the port the database publishes on the host. The services reach it inside the Compose network on 3306 whatever `MYSQL_PORT` is, so changing it never breaks the application's connection.
- **PHP edits.** The dev image keeps `opcache.validate_timestamps` on, so an edit to a PHP file applies without restarting php-fpm (PHP rechecks a file at most every two seconds). The production image turns it off.
- **nginx and `application`.** nginx resolves the `application` address through Docker's DNS for each request, so recreating `application` (`docker compose up -d --force-recreate application`) needs no restart of `web-server`.
- **Schema.** The one-shot `migrations` service waits for the database and runs `migrations:migrate --no-interaction --allow-no-migration`, as the production entrypoint does. If the wait fails, it prints the last attempt's output, not only "Database did not answer".
- **Security signature.** The PHP services fall back to a public, development-only value, `dev-only-insecure-signature-never-use-in-production`, when `SECURITY_SIGNATURE` is empty or unset. Anyone can sign a token with it, so do not expose the dev stack beyond your machine. To use your own, set `SECURITY_SIGNATURE` (at least 32 characters) in `.env`. Which `.env` Compose reads depends on how you start the stack: `docker compose ...` from the repository root (the root `docker-compose.yml` includes the dev stack, and `make up` does this) reads the root `.env`; `docker compose -f docker/dev/docker-compose.yml ...` reads `docker/dev/.env` instead, so with that form a root `.env` needs `--env-file .env`. The two forms also name the Compose project differently (the repository directory against `dev`), and with it the named volumes, so do not alternate between them on one database. A signature in `config/local.neon` has no effect here, because the environment variable always wins over a configuration file.
- **Frontend manifest.** contributte/vite needs a `manifest.json` to compile the DI container, and only a frontend build writes one, into `public/.vite/`. The PHP services therefore load their operator configuration from `FB_CONFIG_DIR=/tmp/fastybird-dev-config`, a directory inside each container that `docker/dev/php/docker-entrypoint.sh` fills when the container starts. It always links `config/local.neon` there, so your overrides load as before. Without a build, it also links `docker/dev/php/no-frontend-build/common.neon`, which sets an empty placeholder manifest kept beside it, and logs `No frontend build in public/` to stderr. Nothing is written into `public/` or the rest of the checkout. `docker compose exec` sees the same choice as the service it runs in. After a build (`docker compose -f docker/dev/docker-compose.yml run --rm --no-deps ui-server pnpm build`), restart the PHP services (`docker compose -f docker/dev/docker-compose.yml restart application ws-server devices-module`) and they use the real manifest. The two cases load different configuration files, so each gets its own compiled container in `var/temp/cache`.

Default services: `web-server` (nginx), `application` (php-fpm with xdebug), `ui-server` (node running `pnpm dev`), `ws-server`, `devices-module` (exchange), `migrations` (one-shot) and `database` (MariaDB). `redis`, `couchdb`, `rabbitmq` and `mqtt` are behind Compose profiles -- see [configuration.md](./configuration.md) for which extension needs which profile. A LAN-facing macvlan network is available as an override: `docker compose -f docker/dev/docker-compose.yml -f docker/dev/docker-compose.lan.yml up -d`.

## Debian packaging (unsupported)

`build/debian/` carries the old miniserver repository's `control`, `postinst`, `prerm`, `fb-miniserver.service` and `make_deb.sh`, ported as a starting point for a later appliance-style install path. It is not validated against the current PHP 8.4 / MariaDB stack and should not be used to deploy this application today -- use Docker.
