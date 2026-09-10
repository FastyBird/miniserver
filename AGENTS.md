# AGENTS.md

This file mirrors [CLAUDE.md](./CLAUDE.md) for agent tooling that reads `AGENTS.md` instead. Keep both in sync.

## Requirements

PHP 8.2, Node 20, yarn 1. The host you run on may differ; it does not count -- every verification command runs in the PHP 8.2 / Node 20 containers described in `docs/baseline.md`.

## Commands

```bash
make lint && make cs && make phpstan && make tests   # PHP quality gate
yarn lint:js && yarn types && yarn build              # JS quality gate
```

`yarn install` always needs `--ignore-engines` (a transitive dependency wants Node >= 22; this project is frozen at Node 20). `make composer-validate` runs `composer validate` deliberately without `--strict` -- two pre-existing warnings make `--strict` exit 1 and that is permanent, not a bug to fix.

## Layout

`src/FastyBird/<Type>/<Name>/` holds 35 extensions, each with its own `src/`, `tests/`, optional `assets/`, `docs/`, `README.md`, `composer.json` and (for the 9 with a frontend) `package.json`. `config/` is the shipped wiring; `config/local.neon` is git-ignored and holds local overrides and secrets. The console is invoked as `php bin/fb-console.php <command>` -- there is no `vendor/bin/fb-console`, and `bin/fb-console` itself is not executable in a fresh checkout. `docs/architecture.md`, `docs/configuration.md` and `docs/deployment.md` are the authoritative references for, respectively, how the application boots and routes requests, how to enable an extension that ships in the tree but is not wired by default, and how the Docker images and supervisor processes are structured.

## Conventions

Conventional commits, required scope, enforced by commitlint (`commitlint.config.js`) and `lint-pr.yml`. See [CONTRIBUTING.md](./CONTRIBUTING.md).

## Do not

- Do not rename a PHP namespace or a `composer.json` package `name` -- the merge design keeps `FastyBird\<Type>\<Name>` and every package name exactly as it was before the merge (out of scope until a later, separate decision).
- Do not reintroduce a committed secret into `.env` or `config/defaults.neon` -- the security signature is generated at container start (see `docker/prod/docker-entrypoint.sh`) or supplied via `FB_APP_PARAMETER__SECURITY_SIGNATURE` / `config/local.neon`.
- Do not document or expect `GET /` to return 200, or a `config/supervisor/*.conf` file to be picked up automatically in production. Both are known, deliberate gaps -- see `docs/architecture.md` and `docs/deployment.md`.
