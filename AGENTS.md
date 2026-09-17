# AGENTS.md

This file mirrors [CLAUDE.md](./CLAUDE.md) for agent tooling that reads `AGENTS.md` instead. Keep both in sync.

## Requirements

PHP 8.4, Node 24, pnpm 10 (pinned via `packageManager` in `package.json`). The host you run on may differ; it does not count -- every verification command runs in the PHP 8.4 / Node 24 containers described in `docs/baseline.md`.

## Commands

```bash
make lint && make cs && make phpstan && make tests   # PHP quality gate
pnpm lint:js && pnpm types && pnpm build              # JS quality gate
```

`make composer-validate` runs `composer validate` deliberately without `--strict` -- two pre-existing warnings make `--strict` exit 1 and that is permanent, not a bug to fix.

## Layout

`src/FastyBird/<Type>/<Name>/` holds 34 extensions, each with its own `src/`, `tests/`, optional `assets/`, `docs/`, `README.md`, `composer.json` and (for the 8 with a frontend) `package.json`. `config/` is the shipped wiring; `config/local.neon` is git-ignored and holds local overrides and secrets. The console is invoked as `php bin/fb-console.php <command>` -- there is no `vendor/bin/fb-console`, and `bin/fb-console` itself is not executable in a fresh checkout. `docs/architecture.md`, `docs/configuration.md` and `docs/deployment.md` are the authoritative references for, respectively, how the application boots and routes requests, how to enable an extension that ships in the tree but is not wired by default, and how the Docker images and supervisor processes are structured.

## Conventions

Conventional commits, required scope, enforced by commitlint (`commitlint.config.cjs`) and `lint-pr.yml`. See [CONTRIBUTING.md](./CONTRIBUTING.md).

## Do not

- Do not rename a PHP namespace or a `composer.json` package `name` on a whim. The one standing exception is absorbing a former third-party first-party library into the tree, where the namespace is deliberately renamed to match its new directory -- `tools/phpcs.xml`'s `rootNamespaces` map and `tools/check-layering.php` both enforce that correspondence, so a half-done rename fails the gates rather than merging quietly.
- Do not trust a gate result after a cross-package edit until the `vendor/fastybird/*` mirrors are refreshed. `COMPOSER_MIRROR_PATH_REPOS=1` copies path repos instead of symlinking them and `composer install` will not replace an unchanged version, so production namespaces keep loading the old copy. `composer reinstall <package>...`, or `rm -rf vendor/fastybird && composer install`. See CLAUDE.md for the full trap.
- Do not reintroduce a committed secret into `.env` or `config/defaults.neon` -- the security signature is generated at container start (see `docker/prod/docker-entrypoint.sh`) or supplied via `FB_APP_PARAMETER__SECURITY_SIGNATURE` / `config/local.neon`.
- Do not document or expect `GET /` to return 200, or a `config/supervisor/*.conf` file to be picked up automatically in production. Both are known, deliberate gaps -- see `docs/architecture.md` and `docs/deployment.md`.
