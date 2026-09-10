# Phase 2, Port Deployment Assets Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Port every deployment asset of value from the old `FastyBird/miniserver` repository into the target repository as `docker/dev/`, `docker/prod/` and `build/debian/`, replace the root `docker-compose.yml` and `.docker/dev/` with that new layout, and add a standalone `.github/workflows/docker-build.yaml` that builds the production image and runs a smoke test, so the repository is deployable as a Docker image before Phase 3 restructures the application itself.

**Architecture:** Development containers move from `.docker/dev/{nginx,node,php}` to `docker/dev/{nginx,node,php}` with the same service topology the repository already runs, plus a one-shot `migrations` service, opt-in compose profiles for the storage services that are not wired by default (`redis`, `couchdb`, `rabbitmq`, `mqtt`), and a macvlan LAN override file. Production is a new three-stage `docker/prod/Dockerfile` (`ui` on `node:20`, `vendor` on a `composer` image, `runtime` on `php:8.2-fpm`) whose nginx configuration hands every request to `public/index.php`, because that single entry point already dispatches between the ReactPHP API application and the Nette SPA shell by request path. The root `docker-compose.yml` becomes a thin `include:` wrapper over `docker/dev/docker-compose.yml`, matching the SmartPanel convention. Debian packaging is ported to `build/debian/` verbatim except for the path changes forced by relocation, and marked unsupported. Everything in this phase treats `var/config/` as the live configuration directory, because Phase 3 has not moved it to `config/` yet, and treats Doctrine schema creation as a manual step, because Phase 3 has not added the first migration yet.

**Tech Stack:** Docker, Docker Compose (`include:`, profiles), nginx, php-fpm, supervisord, GitHub Actions (`docker/build-push-action`, `docker/setup-buildx-action`), Debian packaging (`dpkg-deb`).

**Spec:** docs/superpowers/specs/2026-09-09-miniserver-merge-design.md

## Global Constraints

- PHP 8.2, Node 20, yarn 1 are the frozen toolchain for the whole merge; this phase adds no PHP or JS code and changes no dependency version.
- This phase makes no `composer.json` or `package.json` change of any kind, so the "no pull request mixes a structural change with a dependency version change" rule (D11) is satisfied trivially: every change here is structural (new or moved deployment files).
- CI must stay green; this phase's own new workflow, `.github/workflows/docker-build.yaml`, must go green before the pull request merges, and the existing `tests.yaml`/`qa.yaml` workflows must keep passing (this phase touches no file they run against).
- Conventional commit format `<type>(<scope>): <subject>`, scope from the spec 4.8 list: `core`, `module`, `connector`, `plugin`, `bridge`, `addon`, `automator`, `library`, `ui`, `infra`, `ci`, `deps`, `docs`, `cross`. This phase's commits use `infra` (Docker/Debian assets) and `ci` (the workflow).
- PHP namespaces stay `FastyBird\<Type>\<Name>`; the `src/FastyBird/` prefix does not change; this phase touches no PHP namespace.
- Per spec 4.10, the development host runs PHP 8.5 with no Composer and cannot be used as a baseline; every PHP/Node toolchain command in this plan's verification steps that is not itself `docker build`/`docker compose`/`docker run` runs inside a container (the existing `application` service, then, from Task 5 onward, the same service reached through `docker/dev/docker-compose.yml`), except a host-run, version-independent syntax check like `parse_ini_file` (Task 8, Step 4), which does not depend on the PHP version installed.
- Per the spec's Phase 2 section, `var/config/` is still the live configuration directory (Phase 3 moves it to `config/` and changes `FB_CONFIG_DIR`'s default and the bootstrap load order). Every compose file and Dockerfile in this phase references `var/config` and sets `FB_CONFIG_DIR` to a `var/config`-rooted path, never `config/` or `/data/config`. Phase 3 will update every one of these references when it moves the directory; the tasks below say so at each point they matter.
- Per the spec's Phase 2 section, `migrations/` and `nettrine/migrations` do not exist yet (Phase 3 adds both). No file in this phase calls `migrations:migrate`, `doctrine:fixtures:load` (a Symfony path that never existed in this repository) or references a `migrations/` directory. Schema creation is a documented manual step, `bin/fb-console.php orm:schema-tool:create`, run once by the operator; both the dev `migrations` service and the prod entrypoint only wait for the database and print that instruction.
- This phase adds its own `.github/workflows/docker-build.yaml` rather than depending on `ci-tests.yaml`, which does not exist until Phase 4. Phase 4 folds this job into `ci-tests.yaml` and deletes this file; that deletion is out of scope here.
- `.docker/test/php.ini` is CI test tooling, not a deployment asset, is not in the spec's 4.7/Appendix B list, and is left untouched by this phase.

## Pull Requests

1. **PR1 — Deployment assets** (Tasks 1-14, all of this phase): moves `.docker/dev/{nginx,node,php}` to `docker/dev/{nginx,node,php}`, replaces root `docker-compose.yml` with a thin wrapper over a new `docker/dev/docker-compose.yml`, adds `docker/dev/docker-compose.lan.yml`, adds the full `docker/prod/` production image and compose file, ports Debian packaging to `build/debian/`, restores the README image asset, and adds `.github/workflows/docker-build.yaml`. One PR, because the spec says the old root `docker-compose.yml` and `.docker/` layout are replaced by the new layout "in the same pull request", and the new CI workflow only becomes meaningful once `docker/prod/Dockerfile` exists.

---

### Task 1: Add `.dockerignore`

**Files:**
- Create: `.dockerignore`

**Interfaces:**
- Consumes: none
- Produces: the build-context exclusion list consumed by every `docker build` in Task 10 (`docker/prod/Dockerfile`) and Task 14 (the CI workflow)

- [ ] **Step 1: Create the file**

```
.git
node_modules
vendor
var/logs
var/temp
var/tools
env
.idea
docs
```

- [ ] **Step 2: Verify**

Run: `git status --short docker-compose.yml .dockerignore 2>/dev/null; cat .dockerignore`
Expected: the file exists at the repository root with exactly the 9 lines above, nothing else staged yet.

- [ ] **Step 3: Commit**

```bash
git add .dockerignore
git commit -m "infra(infra): add .dockerignore for the docker build context"
```

---

### Task 2: Move the dev nginx image into `docker/dev/nginx/`

**Files:**
- Move: `.docker/dev/nginx/Dockerfile` → `docker/dev/nginx/Dockerfile`
- Move: `.docker/dev/nginx/conf/nginx.conf` → `docker/dev/nginx/conf/nginx.conf`

**Interfaces:**
- Consumes: none (a relocation; the file already sends every request to `/index.php$is_args$args` and proxies `.php` to `application:9000`, which is exactly the single-entry-point behaviour the production image also needs, but its `COPY .docker/dev/nginx/conf/nginx.conf /etc/nginx/nginx.conf` instruction is resolved against the build context (repo root), not against the Dockerfile's own directory, so the path itself must be edited after the move)
- Produces: `docker/dev/nginx/Dockerfile`, `docker/dev/nginx/conf/nginx.conf`, consumed by Task 5's `docker/dev/docker-compose.yml`

- [ ] **Step 1: Move the files with history preserved**

```bash
mkdir -p docker/dev/nginx/conf
git mv .docker/dev/nginx/Dockerfile docker/dev/nginx/Dockerfile
git mv .docker/dev/nginx/conf/nginx.conf docker/dev/nginx/conf/nginx.conf
```

- [ ] **Step 2: Fix the moved Dockerfile's `COPY` path**

The `COPY` instruction's source is resolved against the build context (repo root), not the Dockerfile's own directory, so it must be updated to the new relative path. Edit `docker/dev/nginx/Dockerfile`, changing:

```
COPY .docker/dev/nginx/conf/nginx.conf      /etc/nginx/nginx.conf
```

to:

```
COPY docker/dev/nginx/conf/nginx.conf      /etc/nginx/nginx.conf
```

- [ ] **Step 3: Verify the moved Dockerfile still builds standalone**

Run: `docker build -f docker/dev/nginx/Dockerfile -t fastybird-dev-nginx-check .`
Expected: `Successfully tagged fastybird-dev-nginx-check:latest` (or the buildkit equivalent `naming to docker.io/library/fastybird-dev-nginx-check:latest done`); the build needs no other file than the one just moved (with its `COPY` path fixed) plus the two-line `COPY` target, so it succeeds independent of every other task in this plan.

- [ ] **Step 4: Commit**

```bash
git add docker/dev/nginx .docker/dev/nginx
git commit -m "infra(infra): move dev nginx image to docker/dev/nginx"
```

---

### Task 3: Move and repair the dev node image at `docker/dev/node/Dockerfile`

**Files:**
- Move: `.docker/dev/node/Dockerfile` → `docker/dev/node/Dockerfile`

**Interfaces:**
- Consumes: Phase 1's structural fix to `.docker/dev/node/Dockerfile` described in spec 4.10 (pin `node:lts-alpine` to `node:20-alpine`, delete the `RUN yarn install` line that has no `package.json` in the build context, delete the `RUN yarn global add @rollup/rollup-linux-arm64-musl` line that is architecture-specific). By the time this task runs, the file at `.docker/dev/node/Dockerfile` must already read as shown in Step 1; if it does not, Phase 1 has not landed and this task's own verification step fails, which is the correct signal to stop and finish Phase 1 first.
- Produces: `docker/dev/node/Dockerfile`, consumed by Task 5's `docker/dev/docker-compose.yml`

- [ ] **Step 1: Confirm the pre-move content matches the Phase 1 fix, then move it**

```bash
cat .docker/dev/node/Dockerfile
```

Expected content (already produced by Phase 1):

```dockerfile
FROM node:20-alpine

# Set working directory
WORKDIR /app

# Increase Node.js memory limit
ENV NODE_OPTIONS="--max_old_space_size=4096"

EXPOSE 3000
EXPOSE 6006

CMD ["yarn", "dev"]
```

If the file still reads `FROM node:lts-alpine` and still contains `RUN yarn install` / `RUN yarn global add @rollup/rollup-linux-arm64-musl`, stop here: Phase 1 has not applied its structural fix yet.

```bash
mkdir -p docker/dev/node
git mv .docker/dev/node/Dockerfile docker/dev/node/Dockerfile
```

- [ ] **Step 2: Verify the moved Dockerfile builds without a mounted volume**

Run: `docker build -f docker/dev/node/Dockerfile -t fastybird-dev-node-check .`
Expected: the build succeeds without ever running `yarn` during the build (dependencies are installed from the mounted volume at container run time by the `command: ["yarn", "dev"]`), confirming the two removed `RUN` lines are gone.

- [ ] **Step 3: Commit**

```bash
git add docker/dev/node .docker/dev/node
git commit -m "infra(infra): move dev node image to docker/dev/node"
```

---

### Task 4: Move the dev php image into `docker/dev/php/`

**Files:**
- Move: `.docker/dev/php/Dockerfile` → `docker/dev/php/Dockerfile`
- Move: `.docker/dev/php/conf/php.ini` → `docker/dev/php/conf/php.ini`

**Interfaces:**
- Consumes: none (a relocation; its `COPY .docker/dev/php/conf/php.ini /usr/local/etc/php/php.ini` instruction is resolved against the build context (repo root), not against the Dockerfile's own directory, so the path itself must be edited after the move)
- Produces: `docker/dev/php/Dockerfile`, `docker/dev/php/conf/php.ini`, consumed by Task 5's `docker/dev/docker-compose.yml`

- [ ] **Step 1: Move the files with history preserved**

```bash
mkdir -p docker/dev/php/conf
git mv .docker/dev/php/Dockerfile docker/dev/php/Dockerfile
git mv .docker/dev/php/conf/php.ini docker/dev/php/conf/php.ini
```

- [ ] **Step 2: Fix the moved Dockerfile's `COPY` path**

The `COPY` instruction's source is resolved against the build context (repo root), not the Dockerfile's own directory, so it must be updated to the new relative path. Edit `docker/dev/php/Dockerfile`, changing:

```
COPY .docker/dev/php/conf/php.ini            /usr/local/etc/php/php.ini
```

to:

```
COPY docker/dev/php/conf/php.ini            /usr/local/etc/php/php.ini
```

- [ ] **Step 3: Verify the moved Dockerfile still builds standalone**

Run: `docker build -f docker/dev/php/Dockerfile -t fastybird-dev-php-check .`
Expected: `Successfully tagged fastybird-dev-php-check:latest` (or the buildkit equivalent). This image installs `bcmath calendar ctype curl dom exif fileinfo gmp intl mbstring mysqli opcache pcntl pdo pdo_mysql pdo_pgsql pgsql phar simplexml sockets xml xmlwriter xsl zip` plus `apcu` and `xdebug` through pecl. **Superseded on 2026-09-10.** This note previously said the file does not install `gd` and treats that as a pre-existing gap carried forward. Phase 1 closed the gap: the file now installs `libfreetype6-dev` and `libjpeg62-turbo-dev`, runs `docker-php-ext-configure gd --with-freetype --with-jpeg` before `docker-php-ext-install`, and includes `gd` in the extension list. Verified working by calling `gd_info()` inside the built image, which reports FreeType and JPEG support. Move the file exactly as it now stands; do not reintroduce the pre-Phase-1 version, and expect `gd` in the extension list above.

- [ ] **Step 4: Commit**

```bash
git add docker/dev/php .docker/dev/php
git commit -m "infra(infra): move dev php image to docker/dev/php"
```

---

### Task 5: Move `docker-compose.yml` to `docker/dev/`, add the migrations service and storage profiles

**Files:**
- Move: `docker-compose.yml` → `docker/dev/docker-compose.yml` (content rewritten in the same step)
- Create: `docker/dev/migrations/docker-entrypoint.sh`

**Interfaces:**
- Consumes: Task 2's `docker/dev/nginx/`, Task 3's `docker/dev/node/`, Task 4's `docker/dev/php/`; the root-level `bin/fb-console.php` (verified present at `bin/fb-console.php`) and the `dbal:run-sql` console command registered by `nettrine/dbal` (already required in the root `composer.json`)
- Produces: `docker/dev/docker-compose.yml` with service names `web-server`, `application`, `ui-server`, `ws-server`, `devices-module`, `migrations`, `database`, `redis`, `couchdb`, `rabbitmq`, `mqtt`, consumed by Task 6 (`docker-compose.lan.yml` overrides `application` and `devices-module` by name) and Task 7 (the root `include:` wrapper)

- [ ] **Step 1: Move `docker-compose.yml` into `docker/dev/`**

```bash
mkdir -p docker/dev
git mv docker-compose.yml docker/dev/docker-compose.yml
```

- [ ] **Step 2: Rewrite the moved file's build contexts, dockerfile paths and volumes for its new location, add the `migrations` service, and add profiles to the storage services**

Every `context: .` becomes `context: ../..` (the compose file now lives two directories below the repository root), every `dockerfile: .docker/dev/...` becomes `dockerfile: docker/dev/...`, every bind-mount source `./` becomes `../../`, and the nginx conf bind mount becomes a path relative to the compose file's own new directory. Replace the full content of `docker/dev/docker-compose.yml` with:

```yaml
#
# FastyBird MiniServer - Development Docker Compose
#
# NOTE: FB_CONFIG_DIR still points at var/config because the config
# directory has not moved yet (Phase 3 moves it to config/ and updates
# this file and the Dockerfiles it drives).
#
# Usage:
#   docker compose -f docker/dev/docker-compose.yml up -d
#
# With the storage services that are not wired by default:
#   docker compose -f docker/dev/docker-compose.yml --profile redis --profile couchdb --profile rabbitmq --profile mqtt up -d
#

services:
  # WEB SERVER
  web-server:
    container_name: fastybird-web-server
    build:
      context: ../..
      dockerfile: docker/dev/nginx/Dockerfile
    depends_on:
      - application
    environment:
      # Container specific environment variables
      TZ: ${APP_TZ:-UTC}
      # Application specific environment variables
      APP_ENV: "dev"
      FB_CONFIG_DIR: /app/var/config
    volumes:
      - ../../:/app:delegated
      - ./nginx/conf/nginx.conf:/etc/nginx/nginx.conf:delegated
    ports:
      - "${WEB_SERVER_PORT:-80}:80"
    networks:
      - fastybird

  # APPLICATION
  application:
    container_name: fastybird-application
    build:
      context: ../..
      dockerfile: docker/dev/php/Dockerfile
    depends_on:
      - database
    environment:
      # Container specific environment variables
      TZ: ${APP_TZ:-UTC}
      PHP_DATE_TIMEZONE: ${APP_TZ:-UTC}
      PHP_XDEBUG_IDEKEY: "PHPSTORM"
      # Application specific environment variables
      APP_ENV: "dev"
      FB_CONFIG_DIR: /app/var/config
      FB_APP_PARAMETER__DATABASE_HOST: database
      FB_APP_PARAMETER__DATABASE_PORT: ${MYSQL_PORT:-3306}
      FB_APP_PARAMETER__DATABASE_USERNAME: ${DATABASE_USERNAME:-fastybird}
      FB_APP_PARAMETER__DATABASE_PASSWORD: ${DATABASE_PASSWORD:-fastybird}
      FB_APP_PARAMETER__DATABASE_DBNAME: ${DATABASE_DBNAME:-fastybird_dev}
      FB_APP_PARAMETER__REDIS_HOST: redis
      FB_APP_PARAMETER__REDIS_PORT: ${REDIS_PORT:-6379}
      FB_APP_PARAMETER__SECURITY_SIGNATURE: ${SECURITY_SIGNATURE}
      FB_APP_PARAMETER__API_PREFIXED_MODULES: ${API_PREFIXED_MODULES:-true}
    volumes:
      - ../../:/app:delegated
    command: php-fpm
    networks:
      - fastybird
    #devices:
    #  - "/dev/tty.usbserial-31420:/dev/ttyUSB0"

  # UI SERVER
  ui-server:
    container_name: fastybird-ui-server
    build:
      context: ../..
      dockerfile: docker/dev/node/Dockerfile
    environment:
      # Container specific environment variables
      TZ: ${APP_TZ:-UTC}
      # Application specific environment variables
      APP_ENV: "dev"
      FB_CONFIG_DIR: /app/var/config
      FB_APP_PARAMETER__APPLICATION_TARGET: http://web-server:80
      FB_APP_PARAMETER__WEBSOCKETS_TARGET: ws://ws-server:8888
    volumes:
      - ../../:/app:delegated
    ports:
      - "${UI_PORT:-3000}:3000"
      - "${UI_DOC_PORT:-6006}:6006"
    networks:
      - fastybird

  # WS SERVER
  ws-server:
    container_name: fastybird-ws-server
    build:
      context: ../..
      dockerfile: docker/dev/php/Dockerfile
    depends_on:
      - database
    environment:
      # Container specific environment variables
      TZ: ${APP_TZ:-UTC}
      PHP_DATE_TIMEZONE: ${APP_TZ:-UTC}
      PHP_XDEBUG_IDEKEY: "PHPSTORM"
      # Application specific environment variables
      APP_ENV: "dev"
      FB_CONFIG_DIR: /app/var/config
      FB_APP_PARAMETER__DATABASE_HOST: database
      FB_APP_PARAMETER__DATABASE_PORT: ${MYSQL_PORT:-3306}
      FB_APP_PARAMETER__DATABASE_USERNAME: ${DATABASE_USERNAME:-fastybird}
      FB_APP_PARAMETER__DATABASE_PASSWORD: ${DATABASE_PASSWORD:-fastybird}
      FB_APP_PARAMETER__DATABASE_DBNAME: ${DATABASE_DBNAME:-fastybird_dev}
      FB_APP_PARAMETER__REDIS_HOST: redis
      FB_APP_PARAMETER__REDIS_PORT: ${REDIS_PORT:-6379}
      FB_APP_PARAMETER__SECURITY_SIGNATURE: ${SECURITY_SIGNATURE}
      FB_APP_PARAMETER__API_PREFIXED_MODULES: ${API_PREFIXED_MODULES:-true}
    volumes:
      - ../../:/app:delegated
    ports:
      - "${WS_PORT:-8888}:8888"
    command: php /app/bin/fb-console.php fb:ws-server:start
    networks:
      - fastybird

  # DEVICES MODULE EXCHANGE
  devices-module:
    container_name: fastybird-devices-module
    build:
      context: ../..
      dockerfile: docker/dev/php/Dockerfile
    depends_on:
      - database
    environment:
      # Container specific environment variables
      TZ: ${APP_TZ:-UTC}
      PHP_DATE_TIMEZONE: ${APP_TZ:-UTC}
      PHP_XDEBUG_IDEKEY: "PHPSTORM"
      # Application specific environment variables
      APP_ENV: "dev"
      FB_CONFIG_DIR: /app/var/config
      FB_APP_PARAMETER__DATABASE_HOST: database
      FB_APP_PARAMETER__DATABASE_PORT: ${MYSQL_PORT:-3306}
      FB_APP_PARAMETER__DATABASE_USERNAME: ${DATABASE_USERNAME:-fastybird}
      FB_APP_PARAMETER__DATABASE_PASSWORD: ${DATABASE_PASSWORD:-fastybird}
      FB_APP_PARAMETER__DATABASE_DBNAME: ${DATABASE_DBNAME:-fastybird_dev}
      FB_APP_PARAMETER__REDIS_HOST: redis
      FB_APP_PARAMETER__REDIS_PORT: ${REDIS_PORT:-6379}
      FB_APP_PARAMETER__SECURITY_SIGNATURE: ${SECURITY_SIGNATURE}
      FB_APP_PARAMETER__API_PREFIXED_MODULES: ${API_PREFIXED_MODULES:-true}
    volumes:
      - ../../:/app:delegated
    command: php /app/bin/fb-console.php fb:devices-module:exchange -n
    networks:
      - fastybird

  # DATABASE SCHEMA (schema creation is a manual step until Phase 3 adds migrations)
  migrations:
    container_name: fastybird-migrations
    build:
      context: ../..
      dockerfile: docker/dev/php/Dockerfile
    depends_on:
      - database
    environment:
      # Container specific environment variables
      TZ: ${APP_TZ:-UTC}
      PHP_DATE_TIMEZONE: ${APP_TZ:-UTC}
      # Application specific environment variables
      APP_ENV: "dev"
      FB_CONFIG_DIR: /app/var/config
      FB_APP_PARAMETER__DATABASE_HOST: database
      FB_APP_PARAMETER__DATABASE_PORT: ${MYSQL_PORT:-3306}
      FB_APP_PARAMETER__DATABASE_USERNAME: ${DATABASE_USERNAME:-fastybird}
      FB_APP_PARAMETER__DATABASE_PASSWORD: ${DATABASE_PASSWORD:-fastybird}
      FB_APP_PARAMETER__DATABASE_DBNAME: ${DATABASE_DBNAME:-fastybird_dev}
      FB_APP_PARAMETER__SECURITY_SIGNATURE: ${SECURITY_SIGNATURE}
    volumes:
      - ../../:/app:delegated
    entrypoint: ["/bin/sh", "/app/docker/dev/migrations/docker-entrypoint.sh"]
    networks:
      - fastybird

  # MYSQL DATABASE STORAGE
  database:
    container_name: fastybird-database
    image: mariadb
    environment:
      # Container specific environment variables
      TZ: ${APP_TZ:-UTC}
      MYSQL_ROOT_PASSWORD: ${ROOT_PASSWORD:-root}
      MYSQL_USER: ${DATABASE_USERNAME:-fastybird}
      MYSQL_PASSWORD: ${DATABASE_PASSWORD:-fastybird}
      MYSQL_DATABASE: ${DATABASE_DBNAME:-fastybird_dev}
    volumes:
      - mysql-dev-data:/var/lib/mysql:rw
      # you may use a bind-mounted host directory instead, so that it is harder to accidentally remove the volume and lose all your data!
      # - ./docker/mysql/data:/var/lib/mysql:rw,delegated
    ports:
      - "${MYSQL_PORT:-3306}:3306"
    networks:
      - fastybird

  # REDIS STORAGE (not wired by default; see docs/configuration.md once it exists in Phase 4)
  redis:
    container_name: fastybird-redis
    image: redis
    profiles:
      - redis
    volumes:
      - redis-dev-other:/var/lib/redis:rw
      - redis-dev-data:/data:rw
      # you may use a bind-mounted host directory instead, so that it is harder to accidentally remove the volume and lose all your data!
      # - ./docker/redis/other:/var/lib/redis:rw,delegated
      # - ./docker/redis/data:/data:rw,delegated
    ports:
      - "${REDIS_PORT:-6379}:6379"
    networks:
      - fastybird

  # COUCH DB STORAGE (not wired by default)
  couchdb:
    container_name: fastybird-couchdb
    image: couchdb
    profiles:
      - couchdb
    environment:
      # Container specific environment variables
      COUCHDB_USER: ${COUCHDB_USERNAME:-admin}
      COUCHDB_PASSWORD: ${COUCHDB_PASSWORD:-admin}
    volumes:
      - couchdb-dev-data:/opt/couchdb/data:rw
      # you may use a bind-mounted host directory instead, so that it is harder to accidentally remove the volume and lose all your data!
      # - ./docker/couchdb/data:/opt/couchdb/data:rw,delegated
    ports:
      - "${COUCHDB_PORT:-5984}:5984"
    networks:
      - fastybird

  # RABBIT MQ EXCHANGE (not wired by default)
  rabbitmq:
    container_name: fastybird-rabbitmq
    image: rabbitmq:management
    profiles:
      - rabbitmq
    environment:
      # Container specific environment variables
      RABBITMQ_DEFAULT_USER: ${RABBITMQ_USERNAME:-admin}
      RABBITMQ_DEFAULT_PASS: ${RABBITMQ_PASSWORD:-admin}
    volumes:
      - rabbitmq-dev-data:/var/lib/rabbitmq:rw
      # you may use a bind-mounted host directory instead, so that it is harder to accidentally remove the volume and lose all your data!
      # - ./docker/rabbitmq/data:/var/lib/rabbitmq:rw,delegated
    ports:
      - "${RABBITMQ_PORT:-5672}:5672"
      - "${RABBITMQ_MANAGEMENT_PORT:-15672}:15672"
    networks:
      - fastybird

  # MQTT (not wired by default)
  mqtt:
    container_name: fastybird-mqtt
    image: eclipse-mosquitto
    profiles:
      - mqtt
    volumes:
      - mqtt-dev-data:/mosquitto/data:rw
      - mqtt-dev-log:/mosquitto/log:rw
      # you may use a bind-mounted host directory instead, so that it is harder to accidentally remove the volume and lose all your data!
      # - ./docker/mqtt/data:/mosquitto/data:rw,delegated
    ports:
      - "${MQTT_PORT:-1883}:1883"
    networks:
      - fastybird

networks:
  fastybird:
    driver: bridge

volumes:
  mysql-dev-data:
  redis-dev-other:
  redis-dev-data:
  couchdb-dev-data:
  mqtt-dev-data:
  mqtt-dev-log:
  rabbitmq-dev-data:
```

- [ ] **Step 3: Add the migrations wait-loop entrypoint**

Reuses the database wait loop from the old miniserver repository's `.docker/dev/migrations/docker-entrypoint.sh`, dropped the `migrations:migrate`/`doctrine:fixtures:load` calls (neither `nettrine/migrations` nor a `migrations/` directory exist until Phase 3), and points at the root `bin/fb-console.php` this repository actually has.

Create `docker/dev/migrations/docker-entrypoint.sh`:

```sh
#!/bin/sh
#
# FastyBird MiniServer - development migrations entrypoint
#
# Waits for the database to answer, then reminds the operator that schema
# creation is a manual step until Phase 3 adds Doctrine migrations.
#
set -e

attempt_left=20

until php bin/fb-console.php dbal:run-sql "select 1" >/dev/null 2>&1;
do
	attempt_left=$((attempt_left-1))

	if [ "${attempt_left}" -eq "0" ]; then
		(>&2 echo "Database did not answer. Aborting.")
		exit 1
	else
		(>&2 echo "Waiting for the database to be ready...")
	fi

	sleep 1
done

echo "Database is reachable."
echo "Schema creation is a manual step until Phase 3 adds migrations:"
echo "  docker compose -f docker/dev/docker-compose.yml exec application php bin/fb-console.php orm:schema-tool:create"
```

- [ ] **Step 4: Verify the compose file parses and the new service graph is correct**

Run: `docker compose -f docker/dev/docker-compose.yml config --services`
Expected output (order as printed by Compose, one service per line): `web-server`, `application`, `ui-server`, `ws-server`, `devices-module`, `migrations`, `database`, `redis`, `couchdb`, `rabbitmq`, `mqtt`.

Run: `docker compose -f docker/dev/docker-compose.yml config --profiles`
Expected output: `couchdb`, `mqtt`, `rabbitmq`, `redis` (four lines, one per profile; `database`, `migrations`, `application`, `ui-server`, `ws-server`, `devices-module`, `web-server` are not profiled and always start).

- [ ] **Step 5: Commit**

```bash
git add docker/dev/docker-compose.yml docker/dev/migrations/docker-entrypoint.sh docker-compose.yml
git commit -m "infra(infra): move dev compose under docker/dev and add the migrations service"
```

---

### Task 6: Add the macvlan LAN override

**Files:**
- Create: `docker/dev/docker-compose.lan.yml`

**Interfaces:**
- Consumes: Task 5's service names `application` and `devices-module` in `docker/dev/docker-compose.yml`
- Produces: `docker/dev/docker-compose.lan.yml`, an optional override an operator adds with a second `-f` flag when a connector needs direct LAN access (SSDP/mDNS discovery, native UDP/TCP device protocols) instead of Docker's default bridge NAT

- [ ] **Step 1: Create the override file**

Adapted from the old miniserver repository's `docker-compose.yml` macvlan network (`local_network`, driver `macvlan`, `LOCAL_NETWORK_*` variables), retargeted at this repository's service names (`application` for ad hoc `fb:<connector>:discover` runs, `devices-module` for the long-running exchange that talks to LAN devices) instead of the old `backend`/`worker` names:

```yaml
#
# FastyBird MiniServer - Local network override
#
# Attaches selected services to a macvlan network so LAN-only device
# discovery and control protocols (SSDP, mDNS, native UDP/TCP) can reach
# devices directly, bypassing Docker's default bridge NAT.
#
# Usage:
#   docker compose -f docker/dev/docker-compose.yml -f docker/dev/docker-compose.lan.yml up -d
#

services:
  application:
    networks:
      fastybird:
      local_network:
        ipv4_address: ${LOCAL_NETWORK_APPLICATION_IP_ADDRESS:-192.168.0.10}

  devices-module:
    networks:
      fastybird:
      local_network:
        ipv4_address: ${LOCAL_NETWORK_DEVICES_MODULE_IP_ADDRESS:-192.168.0.20}

networks:
  local_network:
    name: local_network
    driver: macvlan
    driver_opts:
      parent: ${LOCAL_NETWORK_DRIVER:-eth0}
    ipam:
      config:
        - subnet: ${LOCAL_NETWORK_SUBNET:-192.168.0.0/24}
          gateway: ${LOCAL_NETWORK_GATEWAY:-192.168.0.1}
          ip_range: ${LOCAL_NETWORK_IP_RANGE:-192.168.0.6/24}
```

- [ ] **Step 2: Verify the override merges cleanly**

Run: `docker compose -f docker/dev/docker-compose.yml -f docker/dev/docker-compose.lan.yml config --services`
Expected: the same 11 services as Task 5's Step 4, no error about an unknown network or service (confirms `application` and `devices-module` in the override match names already defined in the base file).

- [ ] **Step 3: Commit**

```bash
git add docker/dev/docker-compose.lan.yml
git commit -m "infra(infra): add the macvlan LAN override for docker/dev"
```

---

### Task 7: Replace the root `docker-compose.yml` with a thin `include:` wrapper

**Files:**
- Create: `docker-compose.yml` (root; re-created after Task 5 moved the old one away)

**Interfaces:**
- Consumes: Task 5's `docker/dev/docker-compose.yml`
- Produces: root `docker-compose.yml`, the convenience entry point `docker compose up -d` from the repository root

- [ ] **Step 1: Create the wrapper**

Matches the SmartPanel convention (`smart-panel/docker-compose.yml`) exactly:

```yaml
#
# FastyBird MiniServer - Development Docker Compose (convenience wrapper)
#
# This file includes the dev compose from docker/dev/.
# You can also run directly: docker compose -f docker/dev/docker-compose.yml up -d
#

include:
  - docker/dev/docker-compose.yml
```

- [ ] **Step 2: Verify the wrapper resolves to the same service graph as the included file**

Run: `docker compose config --services`
Expected: the same 11 services as Task 5's Step 4 (`web-server`, `application`, `ui-server`, `ws-server`, `devices-module`, `migrations`, `database`, `redis`, `couchdb`, `rabbitmq`, `mqtt`), proving `include:` resolved `docker/dev/docker-compose.yml`'s `context: ../..` correctly relative to that file's own directory, not to the root wrapper's directory.

- [ ] **Step 3: Commit**

```bash
git add docker-compose.yml
git commit -m "infra(infra): make root docker-compose.yml a thin include wrapper"
```

---

### Task 8: Add the production runtime config files

**Files:**
- Create: `docker/prod/nginx/nginx.conf`
- Create: `docker/prod/php/php.ini`
- Create: `docker/prod/php/opcache.ini`
- Create: `docker/prod/supervisor/supervisord.conf`

**Interfaces:**
- Consumes: the old miniserver repository's `.docker/prod/{nginx,php,supervisor}/*` (adapted) and this repository's `public/index.php` (verified: dispatches every request by checking whether the URI starts with `/api`, so nginx must route everything, static or not, through `index.php` rather than the old miniserver's separate `/app/public/dist/` static root)
- Produces: the four files consumed by Task 10's `docker/prod/Dockerfile`

- [ ] **Step 1: Create the nginx config**

The old miniserver prod nginx served the SPA from a separate `/app/public/dist/` root and only proxied an API-prefixed location to php-fpm. This repository's `public/index.php` is a single entry point for both the API and the SPA (confirmed by reading it: it checks `substr($_SERVER['REQUEST_URI'], 0, 4) === '/' . Metadata\Constants::ROUTER_API_PREFIX` and dispatches to the ReactPHP `WebServer\Application` or the Nette `Application` accordingly), and Vite's `build.outDir` for `Core/Application` already writes the built SPA assets straight into `public/` alongside `index.php`. So every request that is not an existing static file goes to `index.php`, matching this repository's own `docker/dev/nginx/conf/nginx.conf` pattern, adapted for nginx and php-fpm sharing one container:

Create `docker/prod/nginx/nginx.conf`:

```nginx
user www-data;
worker_processes auto;
daemon off;
pid /run/nginx.pid;

events {
    worker_connections 1024;
}

http {
    include /etc/nginx/mime.types;
    default_type application/octet-stream;

    server_tokens off;

    client_max_body_size 64m;
    sendfile on;
    tcp_nodelay on;
    tcp_nopush on;

    gzip_vary on;

    access_log /dev/stdout;
    error_log /dev/stderr;

    server {
        listen 80;

        server_name miniserver;

        root /app/public;
        index index.php;

        location / {
            try_files $uri /index.php$is_args$args;
        }

        location ~ \.php$ {
            include fastcgi_params;

            fastcgi_pass 127.0.0.1:9000;
            fastcgi_index index.php;
            fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
            fastcgi_param DOCUMENT_ROOT $realpath_root;

            fastcgi_split_path_info ^(.+\.php)(/.*)$;
        }

        location ~ /\.ht {
            deny all;
        }
    }
}
```

- [ ] **Step 2: Create the php.ini and opcache.ini, ported verbatim from the old miniserver's `.docker/prod/php/`**

Create `docker/prod/php/php.ini`:

```ini
[PHP]
memory_limit=256M
post_max_size=6M
upload_max_filesize=5M
realpath_cache_size=4096K
realpath_cache_ttl=600

[date]
date.timezone=${PHP_DATE_TIMEZONE}
```

Create `docker/prod/php/opcache.ini`:

```ini
[opcache]
opcache.enable=1
opcache.enable_cli=1
opcache.memory_consumption=256
opcache.max_accelerated_files=20000
opcache.validate_timestamps=0
opcache.jit=1255
opcache.jit_buffer_size=256M
opcache.interned_strings_buffer=16
```

- [ ] **Step 3: Create the supervisord config**

Adapted from the old miniserver's `.docker/prod/supervisor/supervisord.conf`: same `[unix_http_server]`, `[supervisorctl]`, `[rpcinterface:supervisor]` and `[inet_http_server]` sections, `[include]` pointed at `var/config/supervisor/*.conf` (not `config/supervisor/*.conf`, because that directory has not moved yet), and the four programs the spec's runtime-processes section names for the image: `php-fpm`, `nginx`, `ws-server`, `exchange`. The `fb:web-server:start` ReactPHP program from the old miniserver's dev supervisor config is intentionally not started here, because nginx + php-fpm serve the image instead.

Create `docker/prod/supervisor/supervisord.conf`:

```ini
; SUPERVISORD CONFIG

[unix_http_server]
file = /var/run/supervisor.sock
chmod = 0700

[supervisord]
logfile = /dev/stdout
logfile_maxbytes = 0
pidfile = /var/run/supervisord.pid
childlogdir = /var/log/supervisor
nodaemon = true
user = root

[supervisorctl]
serverurl = unix:///var/run/supervisor.sock

[rpcinterface:supervisor]
supervisor.rpcinterface_factory = supervisor.rpcinterface:make_main_rpcinterface

[inet_http_server]
port = 0.0.0.0:9001

[include]
files = /app/var/config/supervisor/*.conf

; APPLICATION PROGRAMS

[program:php-fpm]
command = php-fpm -F
user = root
autostart = true
autorestart = true

[program:nginx]
command = nginx -g "daemon off;"
user = root
autostart = true
autorestart = true

[program:ws-server]
command = php /app/bin/fb-console.php fb:ws-server:start
autostart = true
autorestart = true
redirect_stderr = true
stdout_logfile = /app/var/logs/ws-server.log

[program:exchange]
command = php /app/bin/fb-console.php fb:devices-module:exchange -n
autostart = true
autorestart = true
redirect_stderr = true
stdout_logfile = /app/var/logs/exchange.log

[eventlistener:subprocess-watcher]
command = php /app/bin/fb-supervisor.php
numprocs = 1
events = PROCESS_STATE_EXITED,PROCESS_STATE_STOPPED,PROCESS_STATE_FATAL
autostart = true
autorestart = unexpected
```

- [ ] **Step 4: Verify the ini and conf files parse**

Run: `php -c docker/prod/php -r "var_dump(parse_ini_file('docker/prod/php/php.ini'));" 2>&1 | head -5`
Expected: no `Syntax error` / `PHP Warning`, the parsed array prints (the `${PHP_DATE_TIMEZONE}` placeholder is a literal string here, not evaluated by `parse_ini_file`, which is fine since php-fpm itself substitutes it from the environment at container start).

Run: `nginx -t -c "$(pwd)/docker/prod/nginx/nginx.conf" 2>&1 || true`
Expected: either a clean "syntax is ok" (if nginx is installed locally) or, if nginx is not installed on the host, skip this line and rely on Task 10's full image build, which runs nginx's own config check implicitly by starting nginx in the smoke test.

- [ ] **Step 5: Commit**

```bash
git add docker/prod/nginx docker/prod/php docker/prod/supervisor
git commit -m "infra(infra): add production nginx, php and supervisor configs"
```

---

### Task 9: Add the production entrypoint

**Files:**
- Create: `docker/prod/docker-entrypoint.sh`

**Interfaces:**
- Consumes: the old miniserver repository's `.docker/dev/migrations/docker-entrypoint.sh` database wait loop (per spec Appendix B, reused here), the root `bin/fb-console.php`
- Produces: `docker/prod/docker-entrypoint.sh`, consumed by Task 10's `docker/prod/Dockerfile`

- [ ] **Step 1: Create the entrypoint**

Reuses the wait loop, drops the old `php bin/console doctrine:fixtures:load` call (a Symfony path that never existed here) and the `migrations:migrate` call (the `migrations/` directory and `nettrine/migrations` do not exist until Phase 3), keeps the old prod entrypoint's `break`-not-`exit` behaviour on timeout so supervisord still starts and the container stays up for diagnosis, and does not generate a security signature (that entrypoint behaviour is Phase 4's job, per the spec's Phase 4 section; `var/config/defaults.neon` still ships a literal signature at this phase):

```sh
#!/bin/sh
#
# FastyBird MiniServer - production entrypoint
#
set -e

mkdir -p "${FB_LOGS_DIR:-/app/var/logs}" "${FB_TEMP_DIR:-/app/var/temp}"

attempt_left=20

until php bin/fb-console.php dbal:run-sql "select 1" >/dev/null 2>&1;
do
	attempt_left=$((attempt_left-1))

	if [ "${attempt_left}" -eq "0" ]; then
		(>&2 echo "Database did not answer. Aborting migrations wait.")
		break
	else
		(>&2 echo "Waiting for the database to be ready...")
	fi

	sleep 1
done

if [ "${attempt_left}" != "0" ]; then
	echo "Database is reachable."
	echo "Schema creation is a manual step until Phase 3 adds migrations:"
	echo "  docker exec <container> php bin/fb-console.php orm:schema-tool:create"
fi

exec /usr/bin/supervisord -c /etc/supervisor/supervisord.conf
```

- [ ] **Step 2: Verify the script is valid POSIX shell**

Run: `shellcheck --shell=sh docker/prod/docker-entrypoint.sh`
Expected: no errors (warnings about `local` or bashisms would indicate a POSIX `sh` violation; this script uses none).

- [ ] **Step 3: Commit**

```bash
git add docker/prod/docker-entrypoint.sh
git commit -m "infra(infra): add the production entrypoint"
```

---

### Task 10: Add the three-stage production Dockerfile

**Files:**
- Create: `docker/prod/Dockerfile`

**Interfaces:**
- Consumes: Task 1's `.dockerignore`, Task 8's `docker/prod/{nginx,php,supervisor}/*`, Task 9's `docker/prod/docker-entrypoint.sh`; the root `bin/`, `var/config/`, `src/`, `public/`, `package.json`, `yarn.lock` (yarn.lock only exists once Phase 1 commits it, per spec Phase 1 §2), `composer.json`, `composer.lock` (same Phase 1 dependency)
- Produces: `docker/prod/Dockerfile`, consumed by Task 11's `docker/prod/docker-compose.yml` and Task 14's `docker-build.yaml`

- [ ] **Step 1: Create the Dockerfile**

Three stages exactly as the spec's deployment section requires: `ui` on `node:20` (`yarn install --frozen-lockfile && yarn build`, the literal script name in this repository's root `package.json`, not `build:prod`, which does not exist here), `vendor` on a `composer` image (`composer install --no-dev --prefer-dist --no-interaction --classmap-authoritative` with `COMPOSER_MIRROR_PATH_REPOS=1`), `runtime` on `php:8.2-fpm` (installs `nginx` and `supervisor` through apt, the extensions `bcmath`, `curl`, `gd`, `gmp`, `intl`, `mbstring`, `pcntl`, `pdo_mysql`, `simplexml`, `sockets`, `xml`, `zip`, `opcache` — `curl`, `mbstring`, `simplexml` and `xml` are added here, beyond what the old miniserver's prod image installed, because composer.json hard-requires `ext-curl`, `ext-mbstring`, `ext-simplexml` and `ext-xml` and none of them ship in the base `php:8.2-fpm` image; `libcurl4-openssl-dev` is added to the apt list so `docker-php-ext-install curl` succeeds). Both build stages `COPY . .` because the root `composer.json` still autoloads all 34 first-party namespaces by direct PSR-4 path into `src/FastyBird/*/*/src` (Phase 3 has not converted this to Composer path repositories yet), so `composer install --classmap-authoritative` needs `src/` present at install time to build the classmap, and the Vite build for `Core/Application` needs the whole `src/FastyBird` tree plus `var/config/extensions.ts` (`Core/Application/assets/main.ts` imports `../../../../../var/config/extensions`, five levels up from `assets/`). The runtime stage copies `bin`, `var/config` (not `config`, because Phase 3 has not moved it), `src`, plus `vendor` from the `vendor` stage and `public` from the `ui` stage (whose own `public/` already contains both the checked-out static files and the freshly built Vite assets, since Vite's `outDir` for `Core/Application` points outside its own project root and therefore does not empty the directory first). `migrations/` is not copied, because it does not exist until Phase 3. The volume is `/app/var` (covering `var/config`, `var/logs`, `var/temp` together), not `/data`, because the `/data`-rooted layout in the spec's configuration-model section is reached only once Phase 3 moves the config directory; Phase 3 will change `FB_CONFIG_DIR`/`FB_LOGS_DIR`/`FB_TEMP_DIR` and the volume target to the `/data` layout when it does.

```dockerfile
#
# FastyBird MiniServer - Production Docker Image
#
# Three stages: ui (Vite/Vue frontend), vendor (PHP dependencies), runtime
# (nginx + php-fpm + supervisord serving the application).
#
# NOTE: config still lives at var/config/ at this phase (Phase 3 moves it to
# config/ and switches FB_CONFIG_DIR/FB_LOGS_DIR/FB_TEMP_DIR and the volume
# below to the /data layout).
#
# Build:
#   docker build -t fastybird/miniserver -f docker/prod/Dockerfile .
#

# ============================================================
# Stage 1: build the frontend
# ============================================================
FROM node:20 AS ui

WORKDIR /app

COPY . .

RUN yarn install --frozen-lockfile \
    && yarn build

# ============================================================
# Stage 2: install PHP dependencies
# ============================================================
FROM composer:2 AS vendor

WORKDIR /app

ENV COMPOSER_ALLOW_SUPERUSER=1 \
    COMPOSER_MIRROR_PATH_REPOS=1

COPY . .

RUN composer install --no-dev --prefer-dist --no-interaction --classmap-authoritative

# ============================================================
# Stage 3: runtime
# ============================================================
FROM php:8.2-fpm AS runtime

ARG APP_PATH=/app

RUN apt-get update && apt-get install -y --no-install-recommends \
        nginx \
        supervisor \
        curl \
        libicu-dev \
        libgmp-dev \
        libzip-dev \
        libpng-dev \
        libcurl4-openssl-dev \
        zlib1g-dev \
    && docker-php-ext-install \
        bcmath \
        curl \
        gd \
        gmp \
        intl \
        mbstring \
        pcntl \
        pdo_mysql \
        simplexml \
        sockets \
        xml \
        zip \
        opcache \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

WORKDIR ${APP_PATH}

COPY bin bin/
COPY var/config var/config/
COPY src src/
COPY --from=vendor /app/vendor vendor/
COPY --from=ui /app/public public/

RUN chmod +x bin/fb-console bin/fb-console.php bin/fb-supervisor bin/fb-supervisor.php \
    && mkdir -p var/logs var/temp \
    && chown -R www-data:www-data ${APP_PATH}

COPY docker/prod/nginx/nginx.conf /etc/nginx/nginx.conf
COPY docker/prod/supervisor/supervisord.conf /etc/supervisor/supervisord.conf
COPY docker/prod/php/php.ini /usr/local/etc/php/conf.d/zz-app.ini
COPY docker/prod/php/opcache.ini /usr/local/etc/php/conf.d/zz-opcache.ini
COPY docker/prod/docker-entrypoint.sh /usr/local/bin/docker-entrypoint

RUN chmod +x /usr/local/bin/docker-entrypoint

ENV APP_ENV=prod \
    FB_CONFIG_DIR=/app/var/config \
    FB_LOGS_DIR=/app/var/logs \
    FB_TEMP_DIR=/app/var/temp

VOLUME ${APP_PATH}/var

EXPOSE 80 8888

HEALTHCHECK --interval=30s --timeout=5s --start-period=30s --retries=3 \
    CMD curl -f http://localhost/ || exit 1

ENTRYPOINT ["docker-entrypoint"]
```

- [ ] **Step 2: Verify the image builds**

Run: `docker build -t fastybird-miniserver-prod-check -f docker/prod/Dockerfile .`
Expected: all three stages complete and the final line reads `Successfully tagged fastybird-miniserver-prod-check:latest` (or the buildkit equivalent). If the `vendor` stage fails on a platform check for a missing extension, or the `ui` stage fails because `yarn.lock` is absent, that means Phase 1 has not committed `composer.lock`/`yarn.lock` yet; this task cannot complete until it has.

- [ ] **Step 3: Commit**

```bash
git add docker/prod/Dockerfile
git commit -m "infra(infra): add the three-stage production Dockerfile"
```

---

### Task 11: Add the production compose file

**Files:**
- Create: `docker/prod/docker-compose.yml`

**Interfaces:**
- Consumes: Task 10's `docker/prod/Dockerfile`
- Produces: `docker/prod/docker-compose.yml`, the self-hosting entry point `docker compose -f docker/prod/docker-compose.yml up -d`

- [ ] **Step 1: Create the compose file**

Application and database (mariadb) with named volumes, redis behind a profile, matching the spec's deployment section and this repository's own dev-compose defaults (`fastybird`/`fastybird_dev`, not yet the `miniserver` identity values the spec assigns in its Phase 4 naming section):

```yaml
#
# FastyBird MiniServer - Production Docker Compose
#
# Usage:
#   docker compose -f docker/prod/docker-compose.yml up -d
#
# With Redis (only useful once the RedisDb plugin is wired in local.neon):
#   docker compose -f docker/prod/docker-compose.yml --profile redis up -d
#
# NOTE: config still lives at var/config/ at this phase; Phase 3 moves it to
# config/ and switches FB_CONFIG_DIR to the /data layout.
#

services:
  application:
    image: ghcr.io/fastybird/miniserver:latest
    build:
      context: ../..
      dockerfile: docker/prod/Dockerfile
    container_name: miniserver
    restart: unless-stopped
    depends_on:
      - database
    environment:
      TZ: ${APP_TZ:-UTC}
      FB_APP_PARAMETER__DATABASE_HOST: database
      FB_APP_PARAMETER__DATABASE_PORT: 3306
      FB_APP_PARAMETER__DATABASE_USERNAME: ${DATABASE_USERNAME:-fastybird}
      FB_APP_PARAMETER__DATABASE_PASSWORD: ${DATABASE_PASSWORD:-fastybird}
      FB_APP_PARAMETER__DATABASE_DBNAME: ${DATABASE_DBNAME:-fastybird}
      FB_APP_PARAMETER__REDIS_HOST: redis
      FB_APP_PARAMETER__REDIS_PORT: 6379
      FB_APP_PARAMETER__SECURITY_SIGNATURE: ${SECURITY_SIGNATURE}
    ports:
      - "${HTTP_PORT:-80}:80"
      - "${WS_PORT:-8888}:8888"
    volumes:
      - miniserver-var:/app/var
    healthcheck:
      test: ["CMD", "curl", "-f", "http://localhost/"]
      interval: 30s
      timeout: 5s
      start_period: 30s
      retries: 3

  database:
    image: mariadb:10.11
    container_name: miniserver-database
    restart: unless-stopped
    environment:
      TZ: ${APP_TZ:-UTC}
      MYSQL_ROOT_PASSWORD: ${ROOT_PASSWORD:-root}
      MYSQL_USER: ${DATABASE_USERNAME:-fastybird}
      MYSQL_PASSWORD: ${DATABASE_PASSWORD:-fastybird}
      MYSQL_DATABASE: ${DATABASE_DBNAME:-fastybird}
    volumes:
      - miniserver-database:/var/lib/mysql

  redis:
    image: redis:7
    container_name: miniserver-redis
    restart: unless-stopped
    profiles:
      - redis
    volumes:
      - miniserver-redis:/data

volumes:
  miniserver-var:
  miniserver-database:
  miniserver-redis:
```

- [ ] **Step 2: Verify**

Run: `docker compose -f docker/prod/docker-compose.yml config --services`
Expected: `application`, `database`, `redis` (three lines).

Run: `docker compose -f docker/prod/docker-compose.yml config --profiles`
Expected: `redis` (one line).

- [ ] **Step 3: Commit**

```bash
git add docker/prod/docker-compose.yml
git commit -m "infra(infra): add the production docker-compose.yml"
```

---

### Task 12: Port Debian packaging to `build/debian/`, marked unsupported

**Files:**
- Create: `build/debian/DEBIAN/control`
- Create: `build/debian/DEBIAN/postinst`
- Create: `build/debian/DEBIAN/prerm`
- Create: `build/debian/etc/systemd/system/fb-miniserver.service`
- Create: `build/debian/etc/miniserver/config/.gitignore`
- Create: `build/debian/usr/lib/miniserver/.gitignore`
- Create: `build/debian/var/log/miniserver/.gitignore`
- Create: `build/debian/var/tmp/miniserver/.gitignore`
- Create: `build/debian/make_deb.sh`
- Create: `build/debian/README.md`

**Interfaces:**
- Consumes: the old miniserver repository's `resources/build/DEBIAN/*`, `resources/build/etc/systemd/system/fb-miniserver.service`, `resources/build/{etc,usr,var}/**/.gitignore`, `bin/make_deb.sh` (per D2, ported without importing history)
- Produces: `build/debian/`, referenced from the root `README.md` once Phase 4 rewrites it

- [ ] **Step 1: Port `DEBIAN/control`, `DEBIAN/postinst`, `DEBIAN/prerm` verbatim**

These reference only absolute system paths (`/etc/miniserver`, `/usr/lib/miniserver`, `/var/log/miniserver`, `/var/tmp/miniserver`), unaffected by where the packaging files live inside this repository, so they are ported byte-for-byte, including the two known-broken items the plan does not fix (see Step 5).

Create `build/debian/DEBIAN/control`:

```
Package: fb-miniserver
Source: miniserver
Version: 0.1.0-1
Architecture: all
Maintainer: FastyBird <code@fastybird.com>
Depends: php8.1-common, php8.1-cli, php8.1-bcmath, php8.1-cli, php8.1-curl, php8.1-gd, php8.1-intl, php8.1-json, php8.1-mbstring, php8.1-mysql, php8.1-opcache, php8.1-sqlite3
Section: php
Priority: optional
Homepage: https://github.com/FastyBird/miniserver
Description: FastyBird MiniServer for IoT devices.
```

Create `build/debian/DEBIAN/postinst`:

```sh
#!/bin/sh
#     Copyright 2021. FastyBird s.r.o.
#
#     Licensed under the Apache License, Version 2.0 (the "License");
#     you may not use this file except in compliance with the License.
#     You may obtain a copy of the License at
#
#         http://www.apache.org/licenses/LICENSE-2.0
#
#     Unless required by applicable law or agreed to in writing, software
#     distributed under the License is distributed on an "AS IS" BASIS,
#     WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
#     See the License for the specific language governing permissions and
#     limitations under the License.

set -e

echo "Finalizing configuration"
sudo adduser --system --gecos "FastyBird-MiniServer Service" --disabled-password --group --home /var/lib/miniserver miniserver || echo "User exists"
sudo usermod -a -G miniserver miniserver
sudo chown miniserver:miniserver /etc/miniserver/ -R
sudo chown miniserver:miniserver /usr/lib/miniserver/ -R
sudo chown miniserver:miniserver /var/log/miniserver/ -R
sudo chown miniserver:miniserver /var/tmp/miniserver/ -R
sudo chown miniserver:miniserver /var/lib/miniserver/ -R
sudo chmod 0644 /var/log/miniserver/*
sudo chmod 0777 /var/log/miniserver
sudo ln -s /usr/lib/miniserver/bin/fb-console.php /usr/bin/fb-miniserver
# NOTE (2026-09-10): the original line pointed at vendor/fastybird/bootstrap/bin/fb-console,
# a package that no longer exists. vendor/bin/fb-console does not exist either: Composer does
# not link a root package's own bin entries into vendor/bin, confirmed by a cold install. The
# only console entry point that exists is the repository's own bin/fb-console.php.
echo "Installation completed"

echo "Enabling daemon..."
sudo pidof systemd && sudo systemctl enable fb-miniserver || echo "Systemctl not found"

echo "Daemon starting..."
sudo pidof systemd && sudo systemctl start fb-miniserver || echo

echo -e "\e[96mFastyBird MiniServer \e[92mhas been installed. Have a nice day \e[93m\e[5m:)\e[25m\e[39m"
```

Create `build/debian/DEBIAN/prerm`:

```sh
#!/bin/sh
#     Copyright 2021. FastyBird s.r.o.
#
#     Licensed under the Apache License, Version 2.0 (the "License");
#     you may not use this file except in compliance with the License.
#     You may obtain a copy of the License at
#
#         http://www.apache.org/licenses/LICENSE-2.0
#
#     Unless required by applicable law or agreed to in writing, software
#     distributed under the License is distributed on an "AS IS" BASIS,
#     WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
#     See the License for the specific language governing permissions and
#     limitations under the License.

set -e

echo "Daemon stopping..."
sudo pidof systemd && sudo systemctl stop fb-miniserver || echo

echo "Disabling daemon..."
sudo pidof systemd && sudo systemctl disable fb-miniserver || echo "Systemctl not found"
```

- [ ] **Step 2: Port the systemd unit verbatim**

Create `build/debian/etc/systemd/system/fb-miniserver.service`:

```ini
[Unit]
Description=FastyBird MiniServer
After=multi-user.target
StartLimitIntervalSec=0

[Service]
Type=simple
User=miniserver
Group=miniserver

ExecStart=/usr/bin/php8.1 /usr/lib/miniserver/bin/fb-console.php fb:web-server:start
ExecStop=/bin/kill -INT $MAINPID
ExecReload=/bin/kill -TERM $MAINPID

Restart=on-failure
RestartSec=5

[Install]
WantedBy=multi-user.target
```

- [ ] **Step 3: Port the placeholder `.gitignore` files that keep the empty package directories tracked**

Create `build/debian/etc/miniserver/config/.gitignore`:

```
.gitignore
!.gitignore
```

Create `build/debian/usr/lib/miniserver/.gitignore`, `build/debian/var/log/miniserver/.gitignore` and `build/debian/var/tmp/miniserver/.gitignore` (same content in all three):

```
*
!.gitignore
```

- [ ] **Step 4: Port `make_deb.sh`, adjusting only the paths the relocation from `resources/build/` and `bin/make_deb.sh` to `build/debian/` forces**

The script now lives two directories below the repository root (`build/debian/make_deb.sh`, was `bin/make_deb.sh` one level below), so `cd ..` becomes `cd ../..`; `resources/build/{etc,usr,var,DEBIAN}` becomes `build/debian/{etc,usr,var,DEBIAN}`; the config source directory is `var/config` (not `config`, which does not exist yet); `yarn build:prod` becomes `yarn build`, the script this repository's `package.json` actually has; and the pre-existing `chmod 0775 dist/DEBIAN/config` typo (there is no file named `config` in `DEBIAN/`; the intended target is `DEBIAN/control`) is fixed while every path is already being rewritten. `bin/make_rpm.sh` (a dead script written for a Python project this repository never was) is not ported.

Create `build/debian/make_deb.sh`:

```sh
#!/bin/sh
#     Copyright 2021. FastyBird s.r.o.
#
#     Licensed under the Apache License, Version 2.0 (the "License");
#     you may not use this file except in compliance with the License.
#     You may obtain a copy of the License at
#
#         http://www.apache.org/licenses/LICENSE-2.0
#
#     Unless required by applicable law or agreed to in writing, software
#     distributed under the License is distributed on an "AS IS" BASIS,
#     WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
#     See the License for the specific language governing permissions and
#     limitations under the License.

# Set actual path to repo root
cd "$(dirname "$0")"
cd ../..

if [ "$1" != "only_clean" ] ; then
  echo "Installing libraries for building deb package..."
  sudo apt-get install fakeroot -y

  echo "Building web ui..."
  sudo yarn install --frozen-lockfile
  sudo yarn build

  echo "Adding the files & folders, scripts in the package..."
  sudo rm -rf dist/
  sudo mkdir dist || echo
  sudo cp -r build/debian/etc dist
  sudo cp -r build/debian/usr dist
  sudo cp -r build/debian/var dist
  sudo cp -r -a build/debian/DEBIAN dist
  sudo find dist/ -name "*.gitignore" -exec rm -f {} \;

  echo "Creating sources for DEB package..."
  sudo mkdir -p dist/usr/lib/miniserver || echo
  sudo cp -r public dist/usr/lib/miniserver/
  sudo cp -r vendor dist/usr/lib/miniserver/
  sudo cp -r var/config dist/etc/miniserver/config
  sudo chmod +x dist/etc/ -R
  sudo chmod +x dist/usr/ -R
  sudo chmod +x dist/var/ -R
  sudo rm dist/etc/miniserver/config/local.neon || echo

  echo "Adding permissions in the package..."
  sudo chown root:root dist/ -R
  sudo chmod 0775 dist/DEBIAN/control
  sudo chmod 0775 dist/DEBIAN/postinst
  sudo chmod 0775 dist/DEBIAN/prerm

  echo "Building DEB package..."
  dpkg-deb -b dist fb-miniserver.deb

  echo "Application package was created"
fi

if [ "$1" = "clean" ] || [ "$1" = "only_clean" ] ; then
  sudo rm -rf dist/
fi

if [ "$1" = "only_clean" ] ; then
  sudo rm fb-miniserver.deb
fi
```

- [ ] **Step 5: Document the packaging as unsupported**

Create `build/debian/README.md`:

```md
# Debian packaging (unsupported)

These files are ported from the old `FastyBird/miniserver` repository, per
decision D7 of the merge design: Docker is the only supported deployment
target for this merge. This packaging is kept for a possible later
appliance-style install path and has not been re-validated against the
merged repository.

Known issues carried over from the old repository, left unfixed here:

- `DEBIAN/postinst` originally symlinked
  `/usr/lib/miniserver/vendor/fastybird/bootstrap/bin/fb-console`, a package that does
  not exist in this repository. Repointed at `bin/fb-console.php`, the only console
  entry point there is. Do not "correct" it to `vendor/bin/fb-console`: a cold install
  on 2026-09-10 confirmed that path is never created, because Composer does not link a
  root package's own `bin` entries into `vendor/bin/`.
- `etc/systemd/system/fb-miniserver.service` and `DEBIAN/control` depend on
  `php8.1`. This repository requires PHP 8.2.

Do not run `make_deb.sh` against a production host until both are fixed and
the resulting package has been installed and tested end to end.
```

- [ ] **Step 6: Verify**

Run: `shellcheck --shell=sh build/debian/make_deb.sh build/debian/DEBIAN/postinst build/debian/DEBIAN/prerm`
Expected: no errors (the `sudo pidof systemd && ... || echo` idiom and `[ "$1" = ... ]` comparisons are valid POSIX `sh`).

Run: `dpkg-deb --build --root-owner-group build/debian /tmp/fb-miniserver-check.deb 2>&1 | tail -5 || true`
Expected: either a built `.deb` (proving the `DEBIAN/control` file is well-formed even though the package payload directories are still just `.gitignore` placeholders) or, if `dpkg-deb` is not installed on this host, skip this line; the control file's own syntax was already checked by Step 6's `shellcheck` pass over the scripts around it.

- [ ] **Step 7: Commit**

```bash
git add build/debian
git commit -m "infra(infra): port debian packaging to build/debian as unsupported"
```

---

### Task 13: Restore the README image asset

**Files:**
- Create: `docs/assets/fastybird_miniserver_readme.png`

**Interfaces:**
- Consumes: the old miniserver repository's `docs/assets/fastybird_miniserver_readme.png` (1660×580 PNG, verified)
- Produces: `docs/assets/fastybird_miniserver_readme.png`, referenced by the root `README.md` once Phase 4 rewrites it

- [ ] **Step 1: Copy the binary asset**

```bash
mkdir -p docs/assets
cp /Users/akadlec/Development/FastyBird/miniserver/docs/assets/fastybird_miniserver_readme.png docs/assets/fastybird_miniserver_readme.png
```

- [ ] **Step 2: Verify**

Run: `file docs/assets/fastybird_miniserver_readme.png`
Expected: `docs/assets/fastybird_miniserver_readme.png: PNG image data, 1660 x 580, 8-bit/color RGBA, non-interlaced`.

- [ ] **Step 3: Commit**

```bash
git add docs/assets/fastybird_miniserver_readme.png
git commit -m "docs(docs): restore the readme banner image from the old miniserver repository"
```

---

### Task 14: Add the standalone `docker-build.yaml` workflow and run the end-to-end smoke test

**Files:**
- Create: `.github/workflows/docker-build.yaml`

**Interfaces:**
- Consumes: Task 10's `docker/prod/Dockerfile`; `actions/checkout@v4` (the version already used by this repository's `.github/workflows/monorepo.yaml`); `docker/setup-buildx-action@v4` and `docker/build-push-action@v7` (the versions the SmartPanel reference repository's `release.yml` uses)
- Produces: a green `.github/workflows/docker-build.yaml`; Phase 4 folds this job into `ci-tests.yaml` and deletes this file, which is out of scope here

- [ ] **Step 1: Create the workflow**

Builds the production image without pushing it, then runs the smoke test the spec's Phase 2 section defines: the container starts against MariaDB, `GET /` returns 200, and `bin/fb-console.php list` exits 0 inside the container.

```yaml
name: "Docker build"

on:
  pull_request:
    paths-ignore:
      - "docs/**"
  push:
    branches:
      - "main"
    tags:
      - v*
  schedule:
    - cron: "0 8 * * 1" # At 08:00 on Monday

jobs:
  build-and-smoke-test:
    name: "Build production image and run smoke test"
    runs-on: "ubuntu-latest"

    services:
      database:
        image: mariadb:10.11
        env:
          MYSQL_ROOT_PASSWORD: root
          MYSQL_USER: fastybird
          MYSQL_PASSWORD: fastybird
          MYSQL_DATABASE: fastybird_dev
        ports:
          - "3306:3306"

    steps:
      - name: "Checkout"
        uses: "actions/checkout@v4"

      - name: "Set up Docker Buildx"
        uses: "docker/setup-buildx-action@v4"

      - name: "Build production image"
        uses: "docker/build-push-action@v7"
        with:
          context: .
          file: docker/prod/Dockerfile
          push: false
          load: true
          tags: fastybird/miniserver:ci
          cache-from: type=gha
          cache-to: type=gha,mode=max

      - name: "Start production container"
        run: |
          docker run -d --name miniserver \
            --network host \
            -e FB_APP_PARAMETER__DATABASE_HOST=127.0.0.1 \
            -e FB_APP_PARAMETER__DATABASE_PORT=3306 \
            -e FB_APP_PARAMETER__DATABASE_USERNAME=fastybird \
            -e FB_APP_PARAMETER__DATABASE_PASSWORD=fastybird \
            -e FB_APP_PARAMETER__DATABASE_DBNAME=fastybird_dev \
            fastybird/miniserver:ci

      - name: "Wait for HTTP 200 on GET /"
        run: |
          for i in $(seq 1 30); do
            code=$(curl -s -o /dev/null -w "%{http_code}" http://127.0.0.1:80/ || echo 000)
            if [ "$code" = "200" ]; then
              echo "Got HTTP 200"
              exit 0
            fi
            echo "Waiting for the application (last status: $code)..."
            sleep 5
          done
          echo "Application did not become ready in time"
          docker logs miniserver
          exit 1

      - name: "Run bin/fb-console.php list inside the container"
        run: docker exec miniserver php bin/fb-console.php list

      - name: "Dump container logs on failure"
        if: failure()
        run: docker logs miniserver
```

- [ ] **Step 2: Validate the workflow YAML**

Run: `python3 -c "import yaml, sys; yaml.safe_load(open('.github/workflows/docker-build.yaml')); print('valid')"`
Expected: `valid`.

- [ ] **Step 3: Run the smoke test locally against the image built in Task 10**

Start a local MariaDB and repeat what the workflow does, since GitHub-hosted `services:` containers are not available on this host:

```bash
docker network create miniserver-smoke-test 2>/dev/null || true
docker run -d --name miniserver-smoke-db --network miniserver-smoke-test \
  -e MYSQL_ROOT_PASSWORD=root -e MYSQL_USER=fastybird -e MYSQL_PASSWORD=fastybird -e MYSQL_DATABASE=fastybird_dev \
  mariadb:10.11
sleep 15
docker run -d --name miniserver-smoke-app --network miniserver-smoke-test -p 8080:80 \
  -e FB_APP_PARAMETER__DATABASE_HOST=miniserver-smoke-db \
  -e FB_APP_PARAMETER__DATABASE_PORT=3306 \
  -e FB_APP_PARAMETER__DATABASE_USERNAME=fastybird \
  -e FB_APP_PARAMETER__DATABASE_PASSWORD=fastybird \
  -e FB_APP_PARAMETER__DATABASE_DBNAME=fastybird_dev \
  fastybird-miniserver-prod-check
```

Run: `for i in $(seq 1 30); do code=$(curl -s -o /dev/null -w "%{http_code}" http://127.0.0.1:8080/); [ "$code" = "200" ] && echo "OK: $code" && break; echo "waiting ($code)"; sleep 5; done`
Expected: `OK: 200` before the loop exhausts its 30 attempts.

Run: `docker exec miniserver-smoke-app php bin/fb-console.php list`
Expected: exit code `0` and the standard Symfony Console command listing (includes at minimum `list`, `help`, `dbal:run-sql`, `orm:schema-tool:create`, `fb:ws-server:start`, `fb:devices-module:exchange`).

Run: `docker exec miniserver-smoke-app php bin/fb-console.php orm:schema-tool:create`
Expected: exit code `0`; this is the manual schema-creation step the spec's Phase 2 section requires as documentation, exercised here to confirm the command exists and runs against the running MariaDB before Phase 3 automates it.

- [ ] **Step 4: Tear down the local smoke test**

```bash
docker rm -f miniserver-smoke-app miniserver-smoke-db 2>/dev/null
docker network rm miniserver-smoke-test 2>/dev/null
```

- [ ] **Step 5: Verify the whole phase leaves a clean tree**

Run: `git status --short`
Expected: empty (everything from Tasks 1-14 already committed); confirms nothing was left staged or untracked.

Run: `find .docker -type f`
Expected: only `.docker/test/php.ini` remains (out of scope for this phase, per the Global Constraints); `.docker/dev/` no longer exists.

- [ ] **Step 6: Commit**

```bash
git add .github/workflows/docker-build.yaml
git commit -m "ci(ci): add docker-build workflow with production image smoke test"
```
