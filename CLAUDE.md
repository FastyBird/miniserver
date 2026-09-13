# FastyBird MiniServer -- AI Agent Instructions

MiniServer is a single PHP (Nette) + Vue application repository, merged from the former `fastybird` framework monorepo and `miniserver` deployment wrapper. Every extension keeps its own backend, frontend, tests, docs and manifests under `src/FastyBird/<Type>/<Name>/` -- there is no `apps/` reshape.

## Requirements

- **PHP**: 8.4
- **Node**: 24
- **Package manager**: yarn 1 (pnpm arrives in Phase 6 of the merge -- do not document or use pnpm before then)

The host you are running on may report a different PHP/Node version. It does not count: every verification command for this project runs in the PHP 8.4 / Node 24 containers described in `docs/baseline.md`.

## Layout

- `src/FastyBird/<Type>/<Name>/` -- 34 extensions across 8 types: Addon (1), Automator (2), Bridge (6), Connector (10), Core (3), Library (1), Module (4), Plugin (7). See `docs/architecture.md` for the full inventory and dependency layering.
- `config/` -- shipped wiring (`common.neon`, `defaults.neon`, `extensions.ts`). `config/local.neon` is git-ignored and is where local overrides and secrets belong. `config/supervisor/` is a legacy, currently-unused layout (see `docs/deployment.md`) -- do not assume it is wired into anything.
- `public/` -- `index.php` is the single entry point for both the JSON:API backend and the Vue SPA shell.
- `bin/` -- `fb-console`/`fb-console.php` (Symfony-style console), `fb-supervisor`/`fb-supervisor.php` (supervisor event listener). Neither is executable in a fresh checkout (mode 644 in git); invoke them as `php bin/fb-console.php <command>`.
- `docker/dev/`, `docker/prod/` -- development and production Docker Compose and Dockerfiles.
- `build/debian/` -- unsupported Debian packaging, kept but not validated.
- `docs/` -- `README.md` index, `architecture.md`, `configuration.md`, `deployment.md`.

## Commands

```bash
# PHP
make cs                 # PHP_CodeSniffer
make csf                # PHP_CodeSniffer, auto-fix
make lint                # php-parallel-lint
make phpstan             # PHPStan, level max
make tests                # PHPUnit via paratest
make composer-validate   # composer validate, root and every extension -- deliberately NOT --strict (two pre-existing warnings are permanent, see Makefile)

# JS
yarn dev                # Vite dev server with hot reload
yarn build              # build every UI package then the application shell
yarn types               # vue-tsc --noEmit across every UI package
yarn lint:js             # ESLint
yarn lint:styles         # stylelint
yarn pretty:check        # Prettier check
```

```bash
# Docker
make up                 # docker-compose up -d      (dev stack: docker/dev/docker-compose.yml)
make down               # docker-compose down
make bash                # shell into the application container as www-data
```

## Console commands (`php bin/fb-console.php <command>`)

Install commands exist per module/connector/addon, not as a single `fb:initialize` (that command does not exist, despite what the old miniserver README claimed): `fb:devices-module:install`, `fb:accounts-module:install`, `fb:triggers-module:install`, `fb:ui-module:install`, `fb:<connector>:install` for each of the ten connectors, `fb:virtual-thermostat-addon:install`, `fb:api-key:create`. Runtime commands: `fb:web-server:start`, `fb:ws-server:start`, `fb:devices-module:exchange`, `fb:devices-module:connector <identifier>`, `fb:<connector>:execute`, `fb:<connector>:discover` (NsPanel, Shelly, Sonoff, Tuya, Viera, Zigbee2Mqtt only), `fb:<bridge>:build` (the three HomeKit bridges). Schema is managed through migrations: `php bin/fb-console.php migrations:migrate --no-interaction --allow-no-migration`.

## Conventions

Conventional commits (`<type>(<scope>): <subject>`), scope required, enforced by commitlint locally and by `lint-pr.yml` on PR titles. See [CONTRIBUTING.md](./CONTRIBUTING.md) for the type and scope tables.

## Architecture reference

Read [docs/architecture.md](./docs/architecture.md) before changing request routing, the config load order, or how extensions register their DI extensions. Read [docs/configuration.md](./docs/configuration.md) before wiring an extension that is present in the tree but not registered by default (RedisDb and its two bridges, RedisDbCache, CouchDb, RabbitMq, the two automators, ApiKey). Read [docs/deployment.md](./docs/deployment.md) before changing anything under `docker/` or `config/supervisor/`.

## Traps already hit once -- do not reintroduce them

- There is no `vendor/bin/fb-console`. Composer does not link a root package's own `bin` entries. Always `php bin/fb-console.php <command>`.
- `composer validate --strict` exits 1 on two permanent warnings (`endroid/qr-code`'s exact version constraint, `mathsolver/mathsolver` unbound). Use `make composer-validate`, never `--strict`.
- `GET /` 404s by design -- `AppRouter` defines a route for it but nothing registers the router. Only `/api/v1` responds. Don't "fix" this as a side effect of unrelated work; it is out of scope for this merge.
- The test suite needs MariaDB and Redis on the *test process's own* loopback (`--network container:fastybird-database`), not compose hostnames. See `docs/baseline.md` and the comment block in `.github/workflows/ci-tests.yaml`.
- Run the tests through `make tests`, never `vendor/bin/paratest` directly. The target puts `tools/php.d/tests.ini` on `PHP_INI_SCAN_DIR`, and without it 1167 of the 1407 tests error. PHP 8.4 deprecates implicitly nullable parameters, vendor code trips it 258 times as composer's eager `autoload.files` entries load, and PHPUnit errors any `@runTestsInSeparateProcesses` test whose child process wrote a single byte to stderr. A `phpunit.xml` `<php><ini>` entry cannot replace this: it is applied long after the autoloader has run, and the forked children inherit the environment, not PHPUnit's configuration.
- Nothing may print to stdout before the DI container's `initialize()` runs. The `php:*-fpm` base images ship no `php.ini`, so `display_errors` defaults to On; one stray notice sets `headers_sent()` and every Nette header silently becomes a no-op, which is how `GET /` once lost `X-Powered-By: Nette Framework 3` and `X-Frame-Options`. `docker/prod/php/php.ini` turns it off. The Docker Build smoke test is the gate that catches it.
- Tracy must not be enabled under a test runner. It installs a global error handler and never restores it, so PHPUnit's own handler survives its `restore_error_handler()` and starts intercepting diagnostics raised outside any test method, which it then cannot attribute. `Boot\Bootstrap::boot()` guards against this; do not simplify that guard back to the Nette Tester check it grew from.
- Run the gates in the application image, not `fastybird-php84-tools`. The tools image ships PHP's stock `memory_limit=128M`; `docker/dev/php/conf/php.ini` raises it to 512M for the application image. `make tests` under 128M dies part-way with a fatal in `dg/bypass-finals`, and `make cs` dies in PHP_CodeSniffer's `Cache.php`, both of which look like the change under test rather than the harness.
- A green verdict is worth only as much as the harness that produced it. Four sources of false green have bitten this repo: a stale `vendor/fastybird/*` mirror (`COMPOSER_MIRROR_PATH_REPOS=1` copies path repos, it does not symlink, so production namespaces load the copy and not `src/`); a warm `var/temp/cache` container cache; a leftover `public/.vite/manifest.json` from an old `yarn build` that made a test pass locally and fail in CI; and piping a gate through `tail`, which reports the exit status of `tail` and turned a `make: *** Error 255` into a task that recorded success. Pipe to a file and check the command's own status, and sanity-check any harness with a known-positive control before trusting a batch verdict.
