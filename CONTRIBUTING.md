# Contributing to FastyBird MiniServer

Thanks for contributing. This document covers the conventions that are enforced automatically -- commit messages and PR titles.

For development setup, see [README.md](./README.md). For architecture, configuration and deployment, see [docs/](./docs/).

## Commit messages and PR titles

Conventional commits, one logical change per commit:

```
<type>(<scope>): <subject>
```

The scope is required on both local commit messages and PR titles. `commitlint` enforces this on every commit via the husky `commit-msg` hook (see [`commitlint.config.cjs`](./commitlint.config.cjs)), and [`lint-pr.yml`](./.github/workflows/lint-pr.yml) enforces it again on the PR title.

### Types

| Type | Use for |
|---|---|
| `feat` | A new feature |
| `fix` | A bug fix |
| `docs` | Documentation only |
| `style` | Formatting, whitespace -- no behaviour change |
| `refactor` | Restructuring that neither fixes a bug nor adds a feature |
| `test` | Adding or correcting tests |
| `chore` | Maintenance, tooling, dependency bumps |
| `perf` | A performance improvement |
| `ci` | CI configuration and workflows |
| `build` | Build system, packaging, toolchain |
| `revert` | Reverting a previous commit |

### Scopes

The scope is the surface you changed, mirroring the extension type directories under `src/FastyBird/`.

| Scope | Covers |
|---|---|
| `core` | `src/FastyBird/Core/**` -- Application, Exchange, Tools |
| `module` | `src/FastyBird/Module/**` -- Accounts, Devices, Triggers, Ui |
| `connector` | `src/FastyBird/Connector/**` -- the ten device connectors |
| `plugin` | `src/FastyBird/Plugin/**` -- ApiKey, CouchDb, RabbitMq, RedisDb, RedisDbCache, WebServer, WsServer |
| `bridge` | `src/FastyBird/Bridge/**` -- the six inter-extension bridges |
| `addon` | `src/FastyBird/Addon/**` -- VirtualThermostat |
| `automator` | `src/FastyBird/Automator/**` -- DateTime, DevicesModule |
| `library` | `src/FastyBird/Library/**` -- Metadata, WebUi |
| `ui` | Root frontend build tooling shared across extensions: `index.html`, `vite.config.ts`, `uno.config.ts`, `eslint.config.mjs`, `prettier.config.mjs`, `stylelint.config.mjs`, `config/extensions.ts` |
| `infra` | `docker/**`, `docker-compose.yml`, `Makefile`, `bin/**`, `config/**` (shipped wiring), root `composer.json`/`tools/**` |
| `ci` | `.github/**` |
| `deps` | Runtime dependency version bumps |
| `deps-dev` | Development-only dependency version bumps -- this is the scope dependabot's `commit-message.include: scope` setting produces for npm/composer devDependency and github-actions updates (see [`.github/dependabot.yml`](./.github/dependabot.yml)) |
| `docs` | `docs/**` and root `*.md` |
| `cross` | Genuinely cross-cutting changes that do not fit a single row above |

Adding a scope means editing three files together: [`commitlint.config.cjs`](./commitlint.config.cjs), [`.github/workflows/lint-pr.yml`](./.github/workflows/lint-pr.yml), and this table.

### Subject

- Lowercase first character, no trailing period.
- Imperative mood ("add", not "added" or "adds").

## Pull requests

- One pull request per logical change. Per the merge design (D11), a pull request never mixes a structural change (a move, rename, deletion or manifest restructure) with a dependency version change.
- CI (`ci-tests.yaml`) must be green before the next pull request starts.
