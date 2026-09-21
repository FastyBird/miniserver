# FastyBird MiniServer -- AI Agent Instructions

MiniServer is a single PHP (Nette) + Vue application repository, merged from the former `fastybird` framework monorepo and `miniserver` deployment wrapper. Every extension keeps its own backend, frontend, tests, docs and manifests under `src/FastyBird/<Type>/<Name>/` -- there is no `apps/` reshape.

## Requirements

- **PHP**: 8.4
- **Node**: 24
- **Package manager**: pnpm 10, pinned via `packageManager` in `package.json`

The host you are running on may report a different PHP/Node version. It does not count: every verification command for this project runs in the PHP 8.4 / Node 24 containers described in `docs/baseline.md`.

## Layout

- `src/FastyBird/<Type>/<Name>/` -- 29 extensions across 7 types: Addon (1), Automator (2), Bridge (6), Connector (10), Core (1), Module (4), Plugin (5). `Library` is no longer a type -- its one package and 14 others merged into `Core/Core` (`fastybird/miniserver-core` / `@fastybird/miniserver-core`). See `docs/architecture.md` for the full inventory and dependency layering.
- `config/` -- shipped wiring (`common.neon`, `defaults.neon`, `extensions.ts`). `config/local.neon` is git-ignored and is where local overrides and secrets belong. `config/supervisor/` is a legacy, currently-unused layout (see `docs/deployment.md`) -- do not assume it is wired into anything.
- `public/` -- `index.php` is the single entry point for both the JSON:API backend and the Vue SPA shell.
- `bin/` -- `fb-console`/`fb-console.php` (Symfony-style console), `fb-supervisor`/`fb-supervisor.php` (supervisor event listener). Neither is executable in a fresh checkout (mode 644 in git); invoke them as `php bin/fb-console.php <command>`.
- `docker/dev/`, `docker/prod/` -- development and production Docker Compose and Dockerfiles.
- `build/debian/` -- unsupported Debian packaging, kept but not validated.
- `docs/` -- `README.md` index, `architecture.md`, `configuration.md`, `deployment.md`.

## Commands

```bash
# PHP
make layers              # dependency direction between packages; plain PHP, runs on a bare checkout
make discriminators      # every Doctrine inheritance root declares an explicit #[ORM\DiscriminatorMap]
make cs                 # PHP_CodeSniffer
make csf                # PHP_CodeSniffer, auto-fix
make lint                # php-parallel-lint
make phpstan             # PHPStan, level max
make tests                # PHPUnit via paratest
make rector              # preview the PHPUnit annotation-to-attribute conversion (dry run)
make rectorf             # apply it, then `make csf` -- Rector emits FQNs the standard rejects
make composer-validate   # composer validate, root and every extension -- deliberately NOT --strict (two pre-existing warnings are permanent, see Makefile)

# JS
pnpm dev                 # Vite dev server with hot reload
pnpm build               # vue-tsc --noEmit, then the Vite build for the application shell
pnpm types                # vue-tsc --noEmit across every UI package
pnpm lint:js              # ESLint
pnpm lint:styles          # stylelint
pnpm pretty:check         # Prettier check
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

Code conventions — identity rules, import aliases, docblocks, naming, PHP idiom — are in
[docs/conventions.md](./docs/conventions.md) and enforced by `make naming` and `make cs`. Read
it before adding a file to `src/FastyBird/Core/Core`.

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
- `composer install` does not refresh the `vendor/fastybird/*` mirror. `COMPOSER_MIRROR_PATH_REPOS=1` copies the path repos rather than symlinking them, and Composer then considers each package already installed at the same version and skips it, so an edit under `src/FastyBird/<Type>/<Name>/src/` has no effect on anything that autoloads the production namespaces -- console commands, `orm:schema-tool`, `migrations:diff`. Use `composer reinstall <package>`, which reports `Mirroring from src/FastyBird/...`. This bites production classes only: PHPUnit loads the files under `tests/` from `src/` directly. It once produced a confident "Nothing to update" from `orm:schema-tool:update`, which would have shipped an entity change with no migration. **A change that spans packages stales every package it touched, not just the one you were working in**: renaming a namespace means rewriting `use` statements in each package that consumes it, and every one of those mirrors is now stale too. `composer reinstall` accepts several names at once; `rm -rf vendor/fastybird && composer install` rebuilds the lot. Skipping that once produced 133 errors out of 141 tests, which read as a botched rename and were entirely a stale mirror. After any cross-package edit, diff `src/FastyBird/<Type>/<Name>/src` against `vendor/fastybird/<package>/src` before believing a red *or* a green.
- `composer update` with no package argument upgrades the entire dependency tree, not just what you changed. Adding a single path repository and running a bare `composer update` once bumped 14 unrelated third-party packages -- Symfony 7.4 to 8.1 among them -- and buried the change under the drift, in a diff that had to be unpicked by hand. Name the package: `composer update fastybird/<name>`. `--with-dependencies` widens it to that package's own requirements, which is usually still more than you meant.
- Test files must be named `*Test.php`. Two files carried a `.phpt` extension and were invisible to every gate at once: `tools/phpunit.xml` `<directory>` entries set no `suffix`, so PHPUnit's default excluded them, and PHPCS and PHPStan only scan `.php`. They had never run, and one of them asserted the opposite of what its three sibling modules assert.
- A green verdict is worth only as much as the harness that produced it. Six sources of false green have bitten this repo: a stale `vendor/fastybird/*` mirror (`COMPOSER_MIRROR_PATH_REPOS=1` copies path repos, it does not symlink, so production namespaces load the copy and not `src/`); a warm `var/temp/cache` container cache; a leftover `public/.vite/manifest.json` from an old `yarn build` that made a test pass locally and fail in CI; piping a gate through `tail`, which reports the exit status of `tail` and turned a `make: *** Error 255` into a task that recorded success; `composer reinstall <package>` run without `COMPOSER_MIRROR_PATH_REPOS=1` (see below); and a bare `docker run` of the application image leaving `date.timezone` empty (see below). Pipe to a file and check the command's own status, and sanity-check any harness with a known-positive control before trusting a batch verdict.
- `composer reinstall <package>` **without** `COMPOSER_MIRROR_PATH_REPOS=1` silently replaces the
  copy with a symlink. CI sets that variable globally (`.github/workflows/ci-tests.yaml`) and so
  does `docker/prod/Dockerfile`, so a copy is the truthful state; a symlinked mirror can never go
  stale and therefore hides exactly the drift the copy would expose, making local runs greener
  than CI. `diff -rq` will not catch it -- a symlink is always "in sync" with its target. Check the
  type: `find vendor/fastybird -maxdepth 1 -type l` must print nothing. Always
  `COMPOSER_MIRROR_PATH_REPOS=1 composer reinstall <package>`, and confirm the output says
  `Mirroring from src/FastyBird/...`.
- Running a gate via a bare `docker run` of the application image, rather than through compose,
  leaves `date.timezone` empty: `docker/dev/php/conf/php.ini` interpolates it from
  `PHP_DATE_TIMEZONE`, which only compose sets. PHP then prints a startup warning **to stdout**,
  which is invisible until something parses a PHP subprocess's stdout -- `EntityMappingTest` boots
  the application in a subprocess and `json_decode`s it, and produced three phantom
  `JsonException: Syntax error` failures from exactly this. Same mechanism as the "nothing may
  print to stdout before `initialize()`" trap, different location. Pass
  `-e TZ=UTC -e PHP_DATE_TIMEZONE=UTC` to any bare `docker run`.
