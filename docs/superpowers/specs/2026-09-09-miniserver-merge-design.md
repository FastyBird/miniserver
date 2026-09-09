# MiniServer Merge Design

- **Date:** 2026-09-09
- **Status:** agreed with Adam Kadlec, all decisions closed
- **Implementation plans:** `docs/superpowers/plans/2026-09-09-phase-*.md` (one plan per phase; the Phase 6 plan is written after Phase 5 completes)

## 1. Goal

Merge the two frozen repositories `FastyBird/fastybird` (framework monorepo) and `FastyBird/miniserver` (application wrapper) into one application repository named `miniserver`. Keep the extension layout in which every extension carries its own backend, frontend, tests, docs and manifests. Make the result deployable as a Docker image, then modernize the toolchain. Delete the old `miniserver` repository and the 25 split-target repositories at the end.

## 2. Background, facts as of 2026-09-09

### 2.1 fastybird repository

- Remote `https://github.com/FastyBird/fastybird.git`, branch `main`, last commit `7fa57e5b` on 2024-11-26, 401 commits, single author.
- 35 packages under `src/FastyBird/<Type>/<Name>/`. Types and counts: Addon 1, Automator 2, Bridge 6, Connector 10, Core 3, Library 2, Module 4, Plugin 7. Full list in Appendix A.
- All 34 packages except `Library/WebUi` contain: `composer.json`, `src/` (PHP, PSR-4 `FastyBird\<Type>\<Name>\`), `tests/cases/unit`, `tools/` (11 QA config files), `.github/` (`FUNDING.yml` plus 4 to 6 workflow files), `Makefile`, `LICENSE.md`, `README.md`, `CHANGELOG-1.0.md`, `.editorconfig`, `.gitattributes`. Present in only some: `tests/fixtures` (25), `tests/tools` (25), `tests/fixtures/dummy` (10), `docs/` (33, absent only from `Library/Metadata`), `.npmignore` (8, the UI packages). Eight packages also carry `assets/` (Vue 3 UI) with `package.json`, `vite.config.ts`, `tsconfig.json` and eslint/prettier configs. Any phase that removes a per-package file must tolerate its absence rather than assume it exists.
- `Library/WebUi` is a nested lerna workspace `@fastybird/web-ui` containing `packages/{components,icons,theme-chalk,utils}`, `web-ui-library` (aggregator) and `docs` (Storybook). It has no `composer.json`. The icons package generates code from 2,037 SVG files; theme-chalk compiles SCSS through gulp.
- Sizes: PHP source 2,801 files and 354,672 lines; PHP tests 365 files, 56,299 lines, 178 test classes; frontend 41,821 lines of TS and Vue across the 8 UI packages; `Library/WebUi` 47,851 lines of TS, Vue and SCSS.
- Root files: `composer.json` (name `fastybird/fastybird`, `type` `application`, `replace` for 34 packages, 34 `autoload.psr-4` and 88 `autoload-dev.psr-4` entries, `bin` entries pointing at the non-existent `src/FastyBird/Library/Application/bin/*`), `package.json` (`@fastybird/fastybird` 1.0.0-dev.24, yarn workspaces, lerna), `lerna.json`, `monorepo-builder.php`, `Makefile`, `docker-compose.yml` (development only), `.docker/dev/{nginx,node,php}`, `.docker/test/php.ini`, `public/index.php`, `public/.htaccess`, `public/.gitignore` (ignores Vite output `assets`, `index.html`, `.vite`), `bin/fb-console`, `bin/fb-console.php`, `bin/fb-supervisor`, `bin/fb-supervisor.php`, `fastybird` (root script including `bin/fb-console.php`), `var/config/common.neon`, `var/config/defaults.neon`, `var/config/extensions.ts`, `var/config/supervisor/{modules/devices-module.conf,plugins/web-server.conf,plugins/ws-server.conf,system/application.conf}`, `tools/{phpcs.xml,phpstan.base.neon,phpstan.src.neon,phpstan.tests.neon,phpstan-bootstrap.php,phpunit.xml,phpunit-bootstrap.php,infection.json,.coveralls.yml}`, `tests/stubs/*.stub` (7 files), `tests/PHPStan/conditional.config.php`, `.github/workflows/{lint,qa,static-analysis,tests,monorepo}.yaml`, `.github/workflows/monorepo-matrix.json`, `.github/FUNDING.yml`, `docs/CNAME` (docs.fastybird.com), `docs/index.md` ("TBD"), `README.md` (empty), `CHANGELOG.md` (stale, 2022), `.env` (committed `SECURITY_SIGNATURE`), `env/.gitignore`, `.editorconfig`, `.gitattributes`, `.browserslistrc`, `babel.config.js`, `commitlint.config.js`. `.gitignore` excludes `composer.lock` and `yarn.lock`.
- `public/index.php` routes requests whose path starts with the API prefix to the ReactPHP `WebServer` application and everything else to the Nette application, which serves the SPA shell through `contributte/vite` and Latte templates. A single entry point therefore serves both API and UI.
- Toolchain: PHP >= 8.2, Node >= 20, yarn 1 with lerna 8, Vite 5, TypeScript 5.6, vue-tsc 2, PHPUnit 10, paratest 7, PHPStan 1.10 with strict rules at level max, Infection 0.27, orisai/coding-standard 3, ESLint 9 flat config, Prettier 3, UnoCSS 0.64, Element Plus 2.8, Vue 3.5, Pinia 2, vue-router 4.4, vue-i18n 10, vue-meta 3 alpha.
- Composer specifics: `doctrine/orm` pinned to `2.15.*`; 10 patch files across 8 target packages, all referenced by raw URL from `https://github.com/FastyBird/libraries-patches` (contributte/monolog 1, dg/bypass-finals 1, doctrine/dbal 1, doctrine/orm 3, nettrine/orm 1, ramsey/uuid-doctrine 1, react/event-loop 1, softcreatr/jsonpath 1); `extra.enable-patching: false` (root patches still apply); dev-branch constraints `bunny/bunny 0.6.x-dev`, `clue/redis-react ^3@dev`, `mathsolver/mathsolver @dev` from a VCS repository; external FastyBird libraries `fastybird/datetime-factory ^0.7.1`, `fastybird/json-api ^0.19`, `fastybird/simple-auth ^0.14`.
- CI uses reusable workflows from the `fastybird/.github` repository: php-lint, php-cs, phpstan, phpunit with MySQL 5.7 and Redis services, coverage to Coveralls, mutations to Stryker, lerna-js-lint, lerna-js-types, prettier. `monorepo.yaml` splits 25 packages to separate repositories through `danharrin/monorepo-split-github-action` on push to `main` and on tags.
- Console commands available through `bin/fb-console`: `fb:web-server:start`, `fb:ws-server:start`, `fb:devices-module:exchange`, `fb:devices-module:connector`, `fb:devices-module:diagnostics`, `fb:devices-module:install`, `fb:accounts-module:install`, `fb:accounts-module:create:account`, `fb:triggers-module:install`, `fb:ui-module:install`, `fb:api-key:create`, `fb:<connector>:install` and `fb:<connector>:execute` for each of the ten connectors, `fb:<connector>:discover` for the six that support discovery (NsPanel, Shelly, Sonoff, Tuya, Viera, Zigbee2Mqtt), `fb:<bridge>:build` for the three HomeKit bridges, `fb:virtual-thermostat-addon:install`. There is no `fb:initialize` command.
- Wiring in `var/config/common.neon`: registered are all three Core packages, the four Modules, the WebServer and WsServer plugins, all ten Connectors, the bridges DevicesModuleUiModule, ShellyConnectorHomeKitConnector, VieraConnectorHomeKitConnector, VirtualThermostatAddonHomeKitConnector, and the VirtualThermostat addon. Not registered: Plugin/ApiKey, Plugin/CouchDb, Plugin/RabbitMq, Plugin/RedisDb, Plugin/RedisDbCache, Bridge/RedisDbPluginDevicesModule, Bridge/RedisDbPluginTriggersModule, Automator/DateTime, Automator/DevicesModule. `Library/Metadata` has no DI extension. `var/config/extensions.ts` registers the UIs of accounts-module, devices-module and homekit-connector only; the triggers-module and ui-module UIs exist but are not registered.
- Frontend consumption today: `src/FastyBird/Core/Application/vite.config.ts` aliases `@fastybird/accounts-module`, `@fastybird/devices-module` and `@fastybird/homekit-connector` to `../../Module/*/assets/entry.ts` in development and to the npm package in production, and builds into the root `public/` with `manifest: true`. `Core/Application/assets/main.ts` imports `../../../../../var/config/extensions` and `./../package.json` for the version string; `App.vue` imports the package description. Cross-extension import specifiers in use: `@fastybird/tools` (93 imports), `@fastybird/web-ui-icons` (52), `@fastybird/metadata-library` (43), `@fastybird/web-ui-library` (39), `@fastybird/vue-wamp-v1` (23, external npm), `@fastybird/devices-module` (5). Nothing imports built `dist/` artifacts. Root `yarn build` runs the lerna build of every UI package as a library and then the application build.
- Bootstrap (`src/FastyBird/Core/Application/src/Boot/Bootstrap.php`) defines `FB_APP_DIR`, `FB_PUBLIC_DIR`, `FB_RESOURCES_DIR`, `FB_TEMP_DIR`, `FB_LOGS_DIR` and `FB_CONFIG_DIR` from `$_ENV` or `getenv()` with defaults `<app>/public`, `<app>/resources`, `<app>/var/temp`, `<app>/var/logs`, and `<app>/config` if that directory exists, otherwise `<app>/var/config`. It loads `Core/Application/config/common.neon` and `defaults.neon`, then `FB_CONFIG_DIR/common.neon`, `defaults.neon` and `local.neon` when present. Environment variables `FB_APP_PARAMETER__<SECTION>_<KEY>` become container parameters. `APP_ENV=dev` enables debug mode.
- Doctrine migrations: no migration files exist anywhere; `Core/Application/migrations/` holds only a `.gitignore`; the schema is created by the `fb:*:install` commands. `nettrine/migrations` is neither required nor wired in fastybird.
- Stale items inside fastybird: the root composer `bin` entries (see above), `nettrineFixtures.paths: %appDir%/fixtures` pointing at a directory that does not exist, `var/config/supervisor/system/application.conf` running `yarn workspace @fastybird/application dev` under supervisor, `package.json` repository URLs pointing at `FastyBird/interface.git`, committed `SECURITY_SIGNATURE` in `.env` and `var/config/defaults.neon`, empty README, stale `docs/` and `CHANGELOG.md`.

### 2.2 miniserver repository

- Remote `https://github.com/FastyBird/miniserver.git`, branch `main`, last commit 2024-08-07, 204 commits, no tags, single author. Its own PHP code was removed on 2022-12-05 and 2022-12-07; everything since is config and dependency bumps.
- Contents: `composer.json` (name `fastybird/miniserver`, requires `fastybird/fastybird: dev-main`, `nettrine/migrations ^0.8`, `nettrine/fixtures ^0.6.3`, `contributte/event-dispatcher`, `contributte/translation`, `vlucas/phpdotenv`; autoload `FastyBird\MiniServer\` to `src/`, which does not exist), `composer.lock` (pins fastybird at commit `abe36f33` from 2024-08-07, before the November 2024 reorganization), `package.json` (`@fastybird/miniserver` 1.0.0, Vite 3, TypeScript 4.8, Node 16, ESLint 8, Prettier 2), `yarn.lock` (links `@fastybird/*` to `../fastybird/src/...` on disk), `config/common.neon`, `config/defaults.neon`, `config/supervisor/.gitignore`, `public/index.php`, `public/.htaccess`, `public/favicon.ico`, `public/icon.png`, `public/robots.txt`, `assets/` (23 files, the 2023 generation of the Vue shell), `index.html`, `vite.config.ts`, `tsconfig.json`, `.eslintrc`, `.eslintignore`, `.prettierrc`, `.stylelintrc.json`, `babel.config.js`, `.browserslistrc`, `Dockerfile` (multi-stage on `node:16` and `fastybird/standard:1.0-headless`), `.docker/dev/Dockerfile` (targets `fastybird_php`, `fastybird_node`, `fastybird_nginx`, `fastybird_migrations`, `fastybird_worker`), `.docker/dev/{nginx/nginx.conf,php/{php.ini,opcache.ini,docker-entrypoint.sh},node/docker-entrypoint.sh,migrations/docker-entrypoint.sh,supervisor/supervisord.conf}`, `.docker/prod/{Dockerfile,docker-entrypoint.sh,nginx/nginx.conf,php/{php.ini,opcache.ini},supervisor/supervisord.conf}`, `.docker/image/{docker-entrypoint.sh,nginx/nginx.conf,php/{php.ini,opcache.ini},supervisor/supervisord.conf}`, `.docker/test.sh`, `docker-compose.yml` (development, adds a macvlan network `local_network`, a `migrations` one-shot service and a `worker` service running supervisord), `docker-compose.prod.yml`, `bin/make_deb.sh`, `bin/make_rpm.sh` (dead, written for a Python project), `resources/build/DEBIAN/{control,postinst,prerm}`, `resources/build/etc/systemd/system/fb-miniserver.service`, `resources/build/{etc/miniserver/config,usr/lib/miniserver,var/log/miniserver,var/tmp/miniserver}/.gitignore`, `.github/workflows/ci.yaml` (inline PHP and JS QA), `.github/workflows/build.yaml` (Docker image to the retired `docker.pkg.github.com` registry, .deb upload on tags), `README.md`, `docs/assets/fastybird_miniserver_readme.png`, `tools/` (QA configs for the non-existent `src/`), `tests/.gitignore`, `.editorconfig` (912 lines of JetBrains settings), `.env` (`SECURITY_SIGNATURE` and `LOCAL_NETWORK_*` values), `migrations/.gitignore`, `env/.gitignore`, `var/`.
- Its config registers `FastyBird\Library\Application\DI\ApplicationExtension`, `FastyBird\Library\Metadata\DI\MetadataExtension` and `FastyBird\Library\Exchange\DI\ExchangeExtension`, and `public/index.php` uses `FastyBird\Library\Bootstrap\Boot`. None of these classes exist in fastybird today, so miniserver cannot boot against current fastybird.
- Identity values: database `fb_miniserver`, user and password `miniserver`, token issuer `com.fastybird.miniserver`, websocket port 8080 (fastybird uses 8888), console name `FastyBird:MiniServer!`.
- Differences from the fastybird config beyond identity: `nettrineMigrations` extension with `directory: %appDir%/migrations`; `fbRedisDbPlugin`, `fbRedisDbPluginDevicesModuleBridge` and `fbRedisDbPluginTriggersModuleBridge` registered; `FB_APP_PARAMETER__API_KEY` passed in compose.
- Dead or broken items: post-install script symlinks `vendor/fastybird/bootstrap/bin/fb-console`, which does not exist; systemd unit uses `php8.1`; Debian control depends on `php8.1-*`; migrations entrypoint calls `bin/console doctrine:fixtures:load`, a Symfony path that does not exist; README documents `fb:initialize`.

### 2.3 Reference project: SmartPanel

`/Users/akadlec/Development/FastyBird/smart-panel` is the convention reference: pnpm 10 workspace, Node 24, `docker/{dev,prod}` with a thin root `docker-compose.yml` using `include:`, `build/` (installer, Raspbian image), `docs/`, `CLAUDE.md`, `AGENTS.md`, `CONTRIBUTING.md`, husky and commitlint with a closed scope list and lowercase subject rule, `.github/dependabot.yml`, `.env.example`. Its `.github/workflows/` holds 12 files; the four worth copying as models are `ci-tests.yaml`, `lint-pr.yml`, `release-drafter.yml` and `release.yml`, the rest being release-channel and deploy jobs specific to SmartPanel. Its production image uses one `/data` volume.

## 3. Decisions

| ID | Decision |
|---|---|
| D1 | The final repository is `FastyBird/miniserver`. The `fastybird` repository keeps its history and is renamed to `miniserver`. The old `miniserver` repository and the 25 split-target repositories are deleted, not archived. GitHub keeps deleted organization repositories restorable for 90 days. |
| D2 | No history is imported from the old miniserver repository. Files of value are ported. |
| D3 | The extension layout stays: `src/FastyBird/<Type>/<Name>/` with `src/`, `tests/`, `assets/`, `docs/`, `README.md`, `composer.json`, `package.json`. There is no SmartPanel style `apps/` reshape and the `src/FastyBird/` prefix is unchanged. |
| D4 | PHP namespaces stay `FastyBird\<Type>\<Name>`. A later rename to `FastyBird\MiniServer\<Type>\<Name>` is out of scope of the merge. |
| D5 | Per-extension manifests are kept and become authoritative: a Composer path repository for PHP and package-manager workspaces for JS. Publishing to Packagist and npm stops. |
| D6 | The unwired extensions (ApiKey, CouchDb, RabbitMq, RedisDb, RedisDbCache, the two Redis bridges, the two automators) stay in the tree and in the manifests. Their infrastructure services become opt-in compose profiles. |
| D7 | Docker is the only supported deployment target for the migration. Debian packaging files are ported to `build/debian/` and marked unsupported. An appliance style install path is a later goal. |
| D8 | Docs stay inside each extension as `README.md` and `docs/`. Root `docs/` holds the index, architecture, configuration and deployment documents. `docs/CNAME` and `docs/index.md` are removed. |
| D9 | The configuration directory is configurable through `FB_CONFIG_DIR` and defaults to `<app>/config`. Shipped wiring always loads from the application folder; only overrides come from `FB_CONFIG_DIR`. System paths use the spelling `miniserver`. |
| D10 | PHP stays. yarn is replaced by pnpm. All dependencies are modernized after the merge in separate pull requests. The only exception is a bump strictly required to make the frozen dependency set install in Phase 1, and each such bump is logged in the pull request description as a forced exception. |
| D11 | No pull request mixes a structural change (a move, rename, deletion or manifest restructure) with a dependency version change. A bump-only pull request may carry several bumps when one blocking failure requires them together. CI is green before the next pull request starts. |

## 4. Target architecture

### 4.1 Repository layout after Phase 5

```
miniserver/
├── src/FastyBird/<Type>/<Name>/        # extensions, see 4.2
├── src/FastyBird/Library/WebUi/        # web-ui workspace library: packages/{components,icons,theme-chalk,utils}, web-ui-library, docs
├── config/                              # shipped wiring: common.neon, defaults.neon, extensions.ts, supervisor/*.conf; local.neon is git-ignored
├── public/                              # index.php, .htaccess, favicon.ico, icon.png, robots.txt; Vite output is git-ignored
├── bin/                                 # fb-console, fb-console.php, fb-supervisor, fb-supervisor.php
├── var/                                 # runtime: logs/, temp/, tools/ (contents git-ignored)
├── env/                                 # dotenv files (git-ignored)
├── migrations/                          # Doctrine migrations, initial one created in Phase 3
├── docker/dev/                          # compose and Dockerfiles for development
├── docker/prod/                         # production image and compose
├── build/debian/                        # unsupported Debian packaging kept for later
├── docs/                                # README.md index, architecture.md, configuration.md, deployment.md, superpowers/{specs,plans}
├── tests/                               # stubs/, PHPStan/conditional.config.php (shared)
├── tools/                               # phpcs.xml, phpstan.neon, phpstan.tests.neon, phpunit.xml, infection.json, bootstraps, patches/*.patch
├── index.html vite.config.ts tsconfig.json uno.config.ts eslint.config.mjs prettier.config.mjs stylelint.config.mjs
├── composer.json composer.lock package.json yarn.lock Makefile docker-compose.yml
├── CLAUDE.md AGENTS.md CONTRIBUTING.md README.md LICENSE.md CHANGELOG.md .env.example .editorconfig .gitattributes .gitignore .dockerignore
└── .github/{workflows/{ci-tests.yaml,lint-pr.yml,release-drafter.yml,release.yml},dependabot.yml,release-drafter.yml,FUNDING.yml}
```

After Phase 6 `yarn.lock` is replaced by `pnpm-lock.yaml` and `pnpm-workspace.yaml`.

### 4.2 Extension anatomy

```
src/FastyBird/Connector/Shelly/
├── src/            # PHP, namespace FastyBird\Connector\Shelly
├── tests/          # cases/unit, fixtures, fixtures/dummy, tools; run by the root phpunit
├── assets/         # optional Vue UI, entry file assets/entry.ts, registered in config/extensions.ts
├── config/         # optional extension-level neon loaded by its DI extension
├── resources/      # optional schemas and templates
├── templates/      # optional Latte templates
├── docs/           # user documentation
├── README.md
├── composer.json   # authoritative, see 4.3
└── package.json    # only when assets/ exists, see 4.4
```

Removed from every extension in Phase 3, each where present rather than assumed: `.github/`, `tools/`, `Makefile`, `LICENSE.md`, `CHANGELOG*.md`, `.editorconfig`, `.gitattributes`, `.npmignore`, `docs/_Footer.md`, and `.gitignore` unless it ignores something that still exists. Removed from UI extensions in Phase 5: `vite.config.ts`, `tsconfig.json`, `eslint.config.mjs`, `prettier.config.mjs`, `.prettierrc`, `.stylelintrc.json`, `jest.config.mjs`, `commitlint.config.mjs`, `.browserslistrc`; `Core/Application` additionally loses `index.html` and `uno.config.ts`, which move to the root.

### 4.3 PHP composition

Extension `composer.json` keeps `name`, `description`, `keywords`, `homepage`, `license`, `authors`, `support`, `require`, `autoload`, `autoload-dev`, `minimum-stability` and `prefer-stable`. It loses `require-dev` (QA tooling lives at the root), `bin`, `config`, `extra.patches`, `extra.branch-alias` and `scripts`. Package names stay exactly as listed in the current root `replace` block.

Root `composer.json` after Phase 3:

- `name`: `fastybird/fastybird` until Phase 4, then `fastybird/miniserver`; `type` changed from its current value `application` to `project`; `license`: `Apache-2.0`.
- `repositories`: `{"type": "path", "url": "src/FastyBird/*/*", "options": {"symlink": true}}` plus the existing `mathsolver/mathsolver` VCS entry.
- `require`: `php`, the `ext-*` list, one line `"fastybird/<package>": "@dev"` for each of the 34 packages, and only what the application shell itself needs (`vlucas/phpdotenv`, `nettrine/migrations`, `nettrine/fixtures`, `contributte/vite`, `contributte/console`, `contributte/translation`, `contributte/event-dispatcher`, `contributte/monolog`, `nette/bootstrap`, `nette/application`, `doctrine/orm`, `cweagans/composer-patches`, `symplify/vendor-patches`). Framework and library packages otherwise come transitively through the extension manifests.
- `require-dev`: the QA tooling currently at the root, kept verbatim (phpunit, paratest, phpstan with its extensions, orisai/coding-standard, infection, php-parallel-lint, dg/bypass-finals, mockery, ninjify/nunjuck, latte, tracy, staabm/annotate-pull-request-from-checkstyle, pds/skeleton, ipub/websockets). `symplify/monorepo-builder` is the only entry removed.
- `autoload`: emptied; the 34 source namespaces come from the path packages instead. `autoload-dev`: all 88 existing test entries kept verbatim. Test directories are not renamed; the mixed lowercase paths stay as they are.
- `extra.patches`: the 8 patches relocated to `tools/patches/*.patch` and referenced by relative path; `extra.enable-patching: false` stays.
- No `replace` block. `config.allow-plugins` unchanged.
- Docker builds and CI export `COMPOSER_MIRROR_PATH_REPOS=1` so path packages are copied instead of symlinked.

Root QA tooling after Phase 3:

- `tools/phpcs.xml`: orisai ruleset 8.2 with the existing exclusions, `TypeNameMatchesFileName.rootNamespaces` regenerated for every extension `src/` and test directory, excludes for `vendor`, `node_modules`, `tools`, `tests/stubs`.
- `tools/phpstan.neon` and `tools/phpstan.tests.neon`: level max, `paths` covering `src/FastyBird/*/*/src` and `src/FastyBird/*/*/tests`, the shared bootstrap defining the `FB_*` constants, stubs from `tests/stubs`, and the union of `ignoreErrors` and `excludePaths` from the 34 per-package `phpstan.config.src.neon` and `phpstan.config.tests.neon` files.
- `tools/phpunit.xml` keeps the existing glob suites; `tools/infection.json` uses `src/FastyBird/*/*/src`.
- `Makefile` keeps `qa`, `cs`, `csf`, `lint`, `phpstan`, `tests`, `tests-simple`, `coverage-clover`, `coverage-html`, `mutations`, `up`, `down`, `bash`, `bash-root`, `list` and adds `composer-validate`, which validates the root and every extension manifest with `--strict`.

### 4.4 Frontend composition

Phase 5, still on yarn:

- Root `package.json`: `name` `@fastybird/miniserver`, `private: true`, `workspaces` `["src/FastyBird/*/*", "src/FastyBird/Library/WebUi/packages/*", "src/FastyBird/Library/WebUi/web-ui-library", "src/FastyBird/Library/WebUi/docs"]`, application `dependencies` and `devDependencies` as the superset of `Core/Application/package.json`, scripts `dev` (`vite --host`), `build:ui` (build web-ui packages in the order utils, icons, theme-chalk, components, web-ui-library), `build` (`yarn build:ui && vue-tsc --noEmit && vite build`), `types`, `lint:js`, `lint:js:fix`, `lint:styles`, `pretty`, `pretty:check`, `pretty:write`, `storybook`.
- Root `index.html` from `Core/Application` with the script tag pointing at `/src/FastyBird/Core/Application/assets/main.ts`; root `vite.config.ts` from `Core/Application` with `build.outDir: 'public'`, `build.manifest: true`, `publicDir: false`, the extension package aliases removed, and a `@config` alias to `config/`; root `tsconfig.json` including `src/FastyBird/*/*/assets/**/*` and `config/**/*.ts`; root `uno.config.ts`, `eslint.config.mjs`, `prettier.config.mjs`, `stylelint.config.mjs`.
- UI extension `package.json`: `name` unchanged, `private: true`, `version` `0.0.0`, `type: module`, `exports: {".": "./assets/entry.ts"}`, `types: ./assets/entry.ts`, `sideEffects: true`, no scripts, `dependencies` listing what `assets/` imports, `peerDependencies` for `vue`, `pinia`, `vue-router`, `vue-i18n`, `vue-meta`, `unocss`, `element-plus`. Workspace packages therefore resolve to source and no library build exists.
- `Core/Application/assets/main.ts` imports the registry through `@config/extensions` and reads the version from a Vite `define` constant `__APP_VERSION__` populated from the root `package.json`; `App.vue` reads `__APP_DESCRIPTION__` the same way.
- `config/extensions.ts` keeps registering accounts-module, devices-module and homekit-connector. Registering triggers-module and ui-module is a functional change and out of scope.
- The web-ui packages keep their build scripts; `@fastybird/web-ui-library` and `@fastybird/web-ui-icons` are consumed as built workspace packages.

Phase 6, first pull request: `pnpm-workspace.yaml` with the same globs, a `packageManager` field, `pnpm-lock.yaml` replacing `yarn.lock`, and every extension manifest declaring every package it imports, because pnpm refuses undeclared imports.

### 4.5 Configuration model

- Shipped wiring lives in `config/common.neon` and `config/defaults.neon`, moved from `var/config/` in Phase 3. Content is the current fastybird root config plus `nettrineMigrations` with `directory: %appDir%/migrations` and `nettrine/migrations` in the root manifest. The default wiring follows fastybird, so Redis, CouchDB and RabbitMQ are not registered by default; `docs/configuration.md` carries copy-and-paste `local.neon` snippets that enable them, including the two Redis bridges.
- Load order after the Phase 3 bootstrap change: `Core/Application/config/common.neon`, `Core/Application/config/defaults.neon`, `FB_APP_DIR/config/common.neon`, `FB_APP_DIR/config/defaults.neon`, then `FB_CONFIG_DIR/common.neon`, `FB_CONFIG_DIR/defaults.neon`, `FB_CONFIG_DIR/local.neon`, each only when the file exists. The bootstrap keeps a list of the absolute real paths it has already added and skips any repeat, so pointing `FB_CONFIG_DIR` at the application config directory, or at a symlink to it, loads those files exactly once. Deduplication is by resolved file path, not by comparing the two directory strings.
- `FB_CONFIG_DIR` defaults to `<app>/config`. The fallback to `<app>/var/config` is removed. The other directory variables keep their existing defaults.
- Environment parameters `FB_APP_PARAMETER__<SECTION>_<KEY>` and `APP_ENV` are unchanged.
- Production image defaults: `FB_CONFIG_DIR=/data/config`, `FB_LOGS_DIR=/data/logs`, `FB_TEMP_DIR=/data/temp`, one volume `/data`.
- Secrets: `defaults.neon` no longer contains a literal `security.signature`; the value must arrive through `FB_APP_PARAMETER__SECURITY_SIGNATURE` or `local.neon`. The production entrypoint writes `local.neon` with a randomly generated signature on first start when neither source provides one. `.env.example` documents the variable.

### 4.6 Runtime processes

- HTTP: nginx in front of php-fpm, every request handed to `public/index.php`. The ReactPHP server behind `fb:web-server:start` remains available for local runs without nginx but is not used by the image.
- Long-running workers under supervisord: `fb:ws-server:start` on port 8888 and `fb:devices-module:exchange`. Connector processes are started with `fb:devices-module:connector <identifier>`, one supervisor program per configured connector, written by the operator into `FB_CONFIG_DIR/supervisor/`. The shipped programs live in `config/supervisor/` and are tracked; anything the operator adds under `FB_CONFIG_DIR/supervisor/` is untracked, and `.gitignore` excludes `config/supervisor/*.local.conf` so operator files in the default location do not show up as repository changes. `docs/deployment.md` documents this template verbatim:

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
- The `fb-supervisor` event listener stops supervisord when a program dies unexpectedly so the container restarts.

### 4.7 Deployment

- `docker/dev/docker-compose.yml`: default services `web-server` (nginx), `application` (php-fpm with xdebug), `ui-server` (node running `yarn dev`), `ws-server`, `devices-module` (exchange), `migrations` (one-shot) and `database` (mariadb). Behind profiles, matching D6 and the production compose: `redis`, `couchdb`, `rabbitmq`, `mqtt`. Redis is a profile because the RedisDb plugin is not wired by default; CI still runs a Redis service unconditionally because parts of the test suite need it. The macvlan network from miniserver lives in `docker/dev/docker-compose.lan.yml` as an override file. Root `docker-compose.yml` is a thin `include:` wrapper as in SmartPanel. Dockerfiles under `docker/dev/{nginx,node,php}`.
- `docker/prod/Dockerfile`: stage `ui` on `node:20` runs `yarn install --frozen-lockfile` and `yarn build`; stage `vendor` on a composer image runs `composer install --no-dev --prefer-dist --no-interaction --classmap-authoritative` with `COMPOSER_MIRROR_PATH_REPOS=1`; stage `runtime` on `php:8.2-fpm` installs nginx and supervisor through apt and the extensions `bcmath`, `gd`, `gmp`, `intl`, `pcntl`, `pdo_mysql`, `sockets`, `zip`, `opcache`, copies `bin`, `config`, `migrations`, `public`, `src`, `vendor`, sets the `/data` defaults from 4.5, declares `VOLUME /data`, `EXPOSE 80 8888`, a healthcheck on `GET /`, and an entrypoint that creates the data directories, generates the signature when missing, waits for the database, runs `bin/fb-console migrations:migrate --no-interaction --allow-no-migration`, and executes supervisord with programs php-fpm, nginx, ws-server and exchange.
- `docker/prod/docker-compose.yml`: `application` and `database` (mariadb) with named volumes; `redis` behind a profile.
- Image name `ghcr.io/fastybird/miniserver`, tag `latest` on `main` and the version on release tags.
- `build/debian/`: ported `control`, `postinst`, `prerm`, `fb-miniserver.service`, `make_deb.sh` and a `README.md` stating that the packaging is unsupported until re-validated. `make_rpm.sh` is not ported.
- `.dockerignore` excludes `.git`, `node_modules`, `vendor`, `var/logs`, `var/temp`, `var/tools`, `env`, `.idea`, `docs`.

### 4.8 CI, release and conventions, Phase 4

- `.github/workflows/ci-tests.yaml` with jobs `php-lint`, `php-cs`, `php-phpstan`, `php-tests` (services `mariadb:10.11` and `redis:7`), `js-lint`, `js-types`, `js-build`, `docker-build` (production image built without push). Runs on pull requests, pushes to `main`, tags and a weekly schedule. Uses `shivammathur/setup-php@v2` with PHP 8.2 and `actions/setup-node@v4` with Node 20 and yarn cache.
- `.github/workflows/release.yml`: on a published release, build and push the production image with the release version tag and `latest`.
- `.github/workflows/lint-pr.yml` and `release-drafter.yml` with `.github/release-drafter.yml`, `.github/dependabot.yml` for composer, npm and github-actions weekly.
- Removed workflows: `lint.yaml`, `qa.yaml`, `static-analysis.yaml`, `tests.yaml`, `monorepo.yaml`, `monorepo-matrix.json`. `FUNDING.yml` stays.
- `commitlint.config.js` with SmartPanel's type list and the scope list `core`, `module`, `connector`, `plugin`, `bridge`, `addon`, `automator`, `library`, `ui`, `infra`, `ci`, `deps`, `docs`, `cross`, required scope, lowercase first character of the subject, no trailing period; husky `commit-msg` hook.
- `CLAUDE.md`, `AGENTS.md`, `CONTRIBUTING.md`, `README.md` rewritten from the miniserver README around commands that exist, `.env.example`, updated `.gitattributes`, the fastybird `.editorconfig` kept.
- Every extension `README.md` loses the badges and links that point at split repositories.

### 4.9 Naming and identity, Phase 4

| Item | Value |
|---|---|
| Composer root name | `fastybird/miniserver` |
| npm root name | `@fastybird/miniserver` |
| Application version | `1.0.0-alpha.1` in root `package.json`; extension `package.json` versions `0.0.0` |
| Console application name | `FastyBird:MiniServer!` |
| Token issuer | `com.fastybird.miniserver` |
| Database defaults | dbname `miniserver`, user `miniserver` |
| Docker image | `ghcr.io/fastybird/miniserver` |
| GitHub repository | `FastyBird/miniserver` |
| Support and repository URLs in manifests | `https://github.com/FastyBird/miniserver` |
| System paths for later packaging | `/etc/miniserver`, `/var/lib/miniserver`, `/var/log/miniserver` |
| Homepage | `https://www.fastybird.com` |

### 4.10 Development and verification environment

Verified on the development host on 2026-09-09: PHP 8.5.6, no Composer installed, Node 24.15.0, yarn 1.22.22, Docker 29.7.2 reachable, no `php@8.2` formula available. The project froze against PHP 8.2 and Node 20, and `>=8.2.0` in the root manifest is satisfied by 8.5 numerically while a two-year-old dependency set is not. A baseline taken on the host would therefore not be the baseline this project needs.

- Every verification command in Phases 1 to 5 runs inside a container, not on the host. Plans state commands in the container form, for example `docker compose exec -T application make phpstan`.
- Until Phase 2 replaces it, the PHP toolchain is the existing `.docker/dev/php/Dockerfile`, which is `php:8.2-fpm` with Composer 2.4 and every required extension already installed, reached through the existing root `docker-compose.yml` service `application`, which mounts the repository at `/app`. No new image is needed for Phase 1.
- `.docker/dev/node/Dockerfile` is defective and is repaired in Phase 1 as a structural fix: it is based on the unpinned `node:lts-alpine`, which now resolves well past Node 20; it runs `yarn install` with no `package.json` present in the image; and it runs `yarn global add @rollup/rollup-linux-arm64-musl`, which is specific to arm64 musl and wrong on other architectures. Pin it to `node:20-alpine` and delete both `RUN` lines, since dependencies install from the mounted volume at run time.
- Phase 2 supersedes both images with `docker/dev/`, after which the same rule points at the new compose file.

Also verified on 2026-09-09: all 10 patch files and the `FastyBird/libraries-patches` repository return HTTP 200, and the `mathsolver/mathsolver` VCS repository is reachable, so Phase 0 and Composer resolution are not blocked by a missing upstream.

## 5. Phases

Each phase is one or more pull requests against `main` of the fastybird repository, each green in CI before the next starts (D11). Commit messages use the conventional format `<type>(<scope>): <subject>` with the scope list from 4.8 from Phase 1 onward, even though enforcement arrives in Phase 4.

### Phase 0, prepare

- Copy all 10 patch files from `https://github.com/FastyBird/libraries-patches` into `tools/patches/` and point the 8 `extra.patches` target entries at the relative paths. All 10 were reachable on 2026-09-09.
- Record the frozen toolchain: `.nvmrc` with `20`, and PHP 8.2 stated in `README.md` once it exists (Phase 4) and in `CLAUDE.md`.
- Bring up the PHP 8.2 toolchain container described in 4.10 and confirm `composer --version` reports 2.x inside it, since the host has no Composer.
- Deliverable: `composer validate` passes inside the container and every patch applies from the local file rather than a URL.

### Phase 1, baseline green

Three pull requests, in order, so that D11 holds:

1. **Structural fixes.** Point the root `bin` entries at `src/FastyBird/Core/Application/bin/*`, replace `nettrineFixtures.paths` with an empty list, and repair `.docker/dev/node/Dockerfile` as described in 4.10. No dependency changes.
2. **Resolution.** Install on PHP 8.2, Node 20 and yarn 1. If the frozen set no longer resolves, bump only what blocks installation, and list every bump in the pull request body under the heading `Forced exceptions` with the error that forced it (D10). Commit `composer.lock` and `yarn.lock` and drop both from `.gitignore`. No moves or renames.
3. **Green.** Fix whatever `make lint`, `make cs`, `make phpstan`, `make tests` against MariaDB and Redis, and `yarn build` still report. If a fix requires a dependency change, it belongs in a follow-up bump-only pull request instead.

- Deliverable: the four make targets and `yarn build` pass locally and in the existing CI workflows, and both lock files are committed.

### Phase 2, port deployment assets

- Everything in 4.7 plus `.dockerignore`, the README image asset, and `build/debian/`.
- Add `.github/workflows/docker-build.yaml`, which builds the production image without pushing and runs the smoke test. Phase 4 folds this job into `ci-tests.yaml` and deletes the standalone file. Phase 2 cannot rely on `ci-tests.yaml`, which does not exist until Phase 4.
- Smoke test: the container starts against MariaDB, `GET /` returns 200, and `bin/fb-console list` exits 0 inside the container. Schema creation stays a documented manual step (`bin/fb-console orm:schema-tool:create`) until Phase 3 provides the first migration.
- Old root files `docker-compose.yml` and `.docker/` are replaced by the new layout in the same pull request.
- Deliverable: `docker-build.yaml` is green, meaning `docker build -f docker/prod/Dockerfile .` succeeds and the smoke test passes.

### Phase 3, one application

- Remove per-extension scaffolding as listed in 4.2 (not the UI build files).
- Convert the root manifest to the path repository model of 4.3: add the path repository, add the 34 `@dev` requirements, empty `autoload.psr-4`, delete the `replace` block, change `type` from `application` to `project`, and repoint `extra.patches` if Phase 0 has not already. Trim every extension manifest. Delete `monorepo-builder.php`, `symplify/monorepo-builder`, `.github/workflows/monorepo.yaml`, `monorepo-matrix.json`, per-extension `wiki.yml` workflows and the root `fastybird` script.
- Consolidate QA configs into the single root files of 4.3.
- Move `var/config/` to `config/` with `git mv`, update every reference (compose files, Dockerfiles, `.gitignore`, `public/index.php`), and implement the bootstrap change of 4.5 with a unit test in `Core/Application/tests`.
- Generate the initial Doctrine migration with `bin/fb-console migrations:diff` against an empty MariaDB and commit it under `migrations/`; the production entrypoint already runs `migrations:migrate`.
- Deliverable: `make composer-validate`, `composer install` with and without `COMPOSER_MIRROR_PATH_REPOS=1`, all QA targets, tests, `yarn build` and the production image pass; a container started with `FB_CONFIG_DIR` pointing at a directory containing only `local.neon` boots.

### Phase 4, rename and conventions

- Apply 4.8 and 4.9. Write `docs/README.md`, `docs/architecture.md`, `docs/configuration.md`, `docs/deployment.md`. Remove `docs/CNAME` and `docs/index.md`. Remove the committed signature and implement the entrypoint generation of 4.5.
- Rename the GitHub repositories: old `miniserver` to `miniserver-old`, then `fastybird` to `miniserver`; update remotes, badges and URLs. Deletions wait for Phase 7.
- Deliverable: `ci-tests.yaml` green on the renamed repository, commitlint enforced locally and on pull request titles, release workflow publishes an image for a pre-release tag.

### Phase 5, frontend consolidation on yarn

- Apply 4.4 for Phase 5. Delete `lerna.json` and lerna.
- Deliverable: `yarn build` from the root produces `public/index.html` and assets, `yarn dev` serves extension sources with hot reload, `yarn types`, `yarn lint:js`, `yarn lint:styles`, `yarn pretty:check` and the web-ui builds pass, no per-extension Vite or tsconfig files remain.

### Phase 6, modernization

The detailed plan is written after Phase 5 completes, because exact versions and breakages are only knowable then. Order of pull requests, each green before the next:

1. pnpm replaces yarn (4.4, Phase 6 part).
2. PHP runtime to the current release in Docker images and CI.
3. QA tooling: PHPStan 2, PHPUnit 12 with attributes instead of annotations, current orisai/coding-standard and Infection.
4. Framework: current Nette, contributte, Symfony 7 components, ReactPHP, ramsey/uuid.
5. Doctrine: ORM 3, DBAL 4, nettrine current. Before upgrading, evaluate each of the 8 patches for necessity; the three ORM patches (UUID persisters and dynamic discriminator map) are the riskiest.
6. Frontend: Node 24, Vite 7, vue-tsc 3, current UnoCSS, Element Plus, Pinia, vue-router, vue-i18n; `vue-meta` 3 alpha replaced by `@unhead/vue` as its own change.
7. External FastyBird libraries `datetime-factory`, `json-api`, `simple-auth` updated in their repositories or folded into `src/FastyBird/Library/`. `mathsolver/mathsolver` pinned to a tag or replaced.

### Phase 7, GitHub cleanup

1. Packagist: mark `fastybird/fastybird` and every split package that exists on Packagist as abandoned with `fastybird/miniserver` as the replacement; point the existing `fastybird/miniserver` entry at the renamed repository. npm: `npm deprecate` every `@fastybird/*` package published from this monorepo with the message `Merged into FastyBird MiniServer`.
2. Delete `FastyBird/miniserver-old` and the 25 split repositories: `application`, `exchange`, `tools`, `metadata-library`, `web-ui-library`, `devices-module`, `accounts-module`, `triggers-module`, `ui-module`, `fb-mqtt-connector`, `homekit-connector`, `modbus-connector`, `ns-panel-connector`, `shelly-connector`, `sonoff-connector`, `tuya-connector`, `viera-connector`, `virtual-connector`, `zigbee2mqtt-connector`, `couchdb-plugin`, `redisdb-plugin`, `rabbitmq-plugin`, `web-server-plugin`, `ws-server-plugin`, `virtual-thermostat-addon`. `FastyBird/libraries-patches` may be deleted once Phase 0 is merged. `FastyBird/.github` stays for the organization profile and README assets.
3. Verify: no reference to a deleted repository remains in the new repository, the release workflow and `ci-tests.yaml` pass after the rename, the published image pulls.

## 6. Whole-merge acceptance

One repository `FastyBird/miniserver` on `main` with green CI, `docker build -f docker/prod/Dockerfile .` producing an image that serves UI and API against MariaDB, all 35 extensions present with their 34 `composer.json` files validated by `composer validate --strict` and their 8 `package.json` files resolved as workspace packages, deployment docs describing configuration and processes, and the old repositories deleted.

## 7. Risks

| Risk | Mitigation |
|---|---|
| The frozen dependency set no longer resolves (dev-branch constraints, VCS package, raw-URL patches) | Phase 0 vendors the patches; Phase 1 allows forced-exception bumps and logs them. Upstream availability was confirmed on 2026-09-09, so the residual risk is constraint drift in the three dev-branch requirements, not a missing source |
| The host toolchain does not match the frozen one, so a host baseline would be misleading | All verification runs in the PHP 8.2 container per 4.10 |
| Doctrine ORM 3 conflicts with the three ORM patches | Phase 6 evaluates each patch before upgrading and treats Doctrine as the last upgrade |
| PHPStan 2 produces a large number of new findings across 355k lines | Own pull request, baseline file allowed temporarily, burned down afterwards |
| Symlinked path packages confuse a tool | `COMPOSER_MIRROR_PATH_REPOS=1` in CI and Docker; tools point at `src/` real paths |
| pnpm strictness exposes undeclared imports | Expected cleanup, contained in the first Phase 6 pull request |
| External base images `fastybird/standard` no longer exist | Production image is rebuilt from official `php`, `node` and `composer` images |
| Deleting repositories is irreversible after 90 days | Packagist abandonment and npm deprecation first, deletion last, after verification |
| No running installation exists to compare behaviour against | Phase 1 establishes the only baseline; every later phase is validated against it |

## 8. Out of scope

- Renaming PHP namespaces or JS package names to a MiniServer prefix.
- Registering the triggers-module and ui-module UIs, or any other functional change.
- Appliance style installation, Raspbian image, installer scripts.
- Folding the three external FastyBird libraries into the repository (decided during Phase 6).
- Any feature work.

## Appendix A, extension inventory

| Extension | Composer name | npm name | Wired in config | PHP lines |
|---|---|---|---|---|
| Addon/VirtualThermostat | fastybird/virtual-thermostat-addon | | yes | 10,522 |
| Automator/DateTime | fastybird/date-time-automator | | no | 924 |
| Automator/DevicesModule | fastybird/devices-module-automator | | no | 2,480 |
| Bridge/DevicesModuleUiModule | fastybird/devices-module-ui-module-bridge | | yes | 3,223 |
| Bridge/RedisDbPluginDevicesModule | fastybird/redisdb-plugin-devices-module-bridge | | no | 1,269 |
| Bridge/RedisDbPluginTriggersModule | fastybird/redisdb-plugin-triggers-module-bridge | | no | 604 |
| Bridge/ShellyConnectorHomeKitConnector | fastybird/shelly-connector-homekit-connector-bridge | | yes | 8,313 |
| Bridge/VieraConnectorHomeKitConnector | fastybird/viera-connector-homekit-connector-bridge | | yes | 6,134 |
| Bridge/VirtualThermostatAddonHomeKitConnector | fastybird/virtual-thermostat-addon-homekit-connector-bridge | | yes | 3,884 |
| Connector/FbMqtt | fastybird/fb-mqtt-connector | | yes | 11,150 |
| Connector/HomeKit | fastybird/homekit-connector | @fastybird/homekit-connector (UI registered) | yes | 24,685 |
| Connector/Modbus | fastybird/modbus-connector | | yes | 17,549 |
| Connector/NsPanel | fastybird/ns-panel-connector | | yes | 40,894 |
| Connector/Shelly | fastybird/shelly-connector | | yes | 23,186 |
| Connector/Sonoff | fastybird/sonoff-connector | | yes | 29,508 |
| Connector/Tuya | fastybird/tuya-connector | | yes | 21,201 |
| Connector/Viera | fastybird/viera-connector | | yes | 16,274 |
| Connector/Virtual | fastybird/virtual-connector | | yes | 7,639 |
| Connector/Zigbee2Mqtt | fastybird/zigbee2mqtt-connector | | yes | 17,301 |
| Core/Application | fastybird/application | @fastybird/application (shell) | yes | 4,371 |
| Core/Exchange | fastybird/exchange | | yes | 1,323 |
| Core/Tools | fastybird/tools | @fastybird/tools | yes | 4,153 |
| Library/Metadata | fastybird/metadata-library | @fastybird/metadata-library | no DI extension | 771 |
| Library/WebUi | none | @fastybird/web-ui plus 4 packages and docs | n/a | 0 |
| Module/Accounts | fastybird/accounts-module | @fastybird/accounts-module (UI registered) | yes | 12,081 |
| Module/Devices | fastybird/devices-module | @fastybird/devices-module (UI registered) | yes | 44,829 |
| Module/Triggers | fastybird/triggers-module | @fastybird/triggers-module (UI not registered) | yes | 10,416 |
| Module/Ui | fastybird/ui-module | @fastybird/ui-module (UI not registered) | yes | 17,401 |
| Plugin/ApiKey | fastybird/apikey-plugin | | no | 715 |
| Plugin/CouchDb | fastybird/couchdb-plugin | | no | 1,105 |
| Plugin/RabbitMq | fastybird/rabbitmq-plugin | | no | 1,198 |
| Plugin/RedisDb | fastybird/redisdb-plugin | | no | 2,934 |
| Plugin/RedisDbCache | fastybird/redisdb-cache-plugin | | no | 1,044 |
| Plugin/WebServer | fastybird/web-server-plugin | | yes | 4,865 |
| Plugin/WsServer | fastybird/ws-server-plugin | | yes | 726 |

PHP dependency layering, from the extension manifests: Library/Metadata has no FastyBird dependency; Core/Tools depends on datetime-factory and metadata-library; Core/Application on simple-auth; Core/Exchange on application and metadata-library; Modules on application, exchange, json-api, metadata-library, simple-auth and tools; Connectors on application, devices-module, metadata-library and tools (HomeKit also on exchange); Plugins on application and tools, most also on metadata-library (exchange for RabbitMq, RedisDb, WsServer; json-api for ApiKey, which does not depend on metadata-library at all; RedisDbCache depends on no FastyBird package); Bridges, the Addon and the Automators on the packages they bridge.

## Appendix B, miniserver assets and their fate

| Asset | Fate |
|---|---|
| `.docker/prod`, `.docker/image`, `Dockerfile`, supervisor and nginx configs | Basis for `docker/prod` (Phase 2) |
| `.docker/dev` migrations and worker targets, `docker-compose.yml` macvlan network | Basis for `docker/dev` and the LAN override (Phase 2) |
| `.docker/dev/migrations/docker-entrypoint.sh` database wait loop | Reused in the production entrypoint (Phase 2) |
| `resources/build/DEBIAN/*`, `fb-miniserver.service`, `bin/make_deb.sh` | Ported to `build/debian/` as unsupported (Phase 2) |
| `.github/workflows/build.yaml` | Rewritten for GHCR as `release.yml` (Phase 4) |
| `README.md`, `docs/assets/fastybird_miniserver_readme.png` | Rewritten and reused (Phase 2 asset, Phase 4 text) |
| `config/common.neon` migrations and Redis wiring, `config/defaults.neon` identity values | Merged into `config/` and `docs/configuration.md` (Phases 3 and 4) |
| `assets/`, `index.html`, `vite.config.ts`, `tsconfig.json`, lint configs | Not ported, superseded by `Core/Application` |
| `tools/`, `tests/`, `.editorconfig`, `bin/make_rpm.sh`, `composer.lock`, `yarn.lock` | Not ported |
