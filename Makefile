_: list

# Config

PHPCS_CONFIG=tools/phpcs.xml
PHPSTAN_SRC_CONFIG=tools/phpstan.src.neon
PHPSTAN_TESTS_CONFIG=tools/phpstan.tests.neon
PHPUNIT_CONFIG=tools/phpunit.xml
INFECTION_CONFIG=tools/infection.json

# QA

qa: ## Check code quality - coding style and static analysis
	make cs & make phpstan

cs: ## Check PHP files coding style
	mkdir -p var/tools/PHP_CodeSniffer
	$(PRE_PHP) "vendor/bin/phpcs" src tests --standard=$(PHPCS_CONFIG) --parallel=$(LOGICAL_CORES) $(ARGS)

csf: ## Fix PHP files coding style
	mkdir -p var/tools/PHP_CodeSniffer
	$(PRE_PHP) "vendor/bin/phpcbf" src tests --standard=$(PHPCS_CONFIG) --parallel=$(LOGICAL_CORES) $(ARGS)

lint:
	$(PRE_PHP) "vendor/bin/parallel-lint" src tests --exclude .git --exclude vendor

phpstan: ## Analyse code with PHPStan
	mkdir -p var/tools
	$(PRE_PHP) "vendor/bin/phpstan" analyse src -c $(PHPSTAN_SRC_CONFIG) $(ARGS)
	$(PRE_PHP) "vendor/bin/phpstan" analyse tests/cases -c $(PHPSTAN_TESTS_CONFIG) $(ARGS)

# Tests

.PHONY: tests
tests: ## Run all tests
	$(PRE_PHP) $(PHPUNIT_COMMAND) $(ARGS)

coverage-clover: ## Generate code coverage in XML format
	$(PRE_PHP) $(PHPUNIT_COVERAGE) --coverage-clover=var/coverage/clover.xml $(ARGS)

coverage-html: ## Generate code coverage in HTML format
	$(PRE_PHP) $(PHPUNIT_COVERAGE) --coverage-html=var/coverage/html $(ARGS)

mutations: ## Check code for mutants
	make mutations-tests
	make mutations-infection

mutations-tests:
	mkdir -p var/coverage
	$(PRE_PHP) $(PHPUNIT_COVERAGE) --coverage-xml=var/coverage/xml --log-junit=var/coverage/junit.xml

mutations-infection:
	$(PRE_PHP) vendor/bin/infection \
		--configuration=$(INFECTION_CONFIG) \
		--threads=$(LOGICAL_CORES) \
		--coverage=../var/coverage \
		--skip-initial-tests \
		$(ARGS)

# Docker

##@ [Docker] Build / Infrastructure
.docker/.env:
	cp $(DOCKER_COMPOSE_DIR)/.env.example $(DOCKER_COMPOSE_DIR)/.env

.PHONY: docker-clean
docker-clean: ## Remove the .env file for docker
	rm -f $(DOCKER_COMPOSE_DIR)/.env

.PHONY: docker-init
docker-init: .docker/.env ## Make sure the .env file exists for docker

## Build all docker images from scratch, without cache etc.
## Build a specific image by providing the service name via: make docker-build-from-scratch CONTAINER=<service>
.PHONY: docker-build-from-scratch
docker-build-from-scratch: docker-init
	docker-compose rm -fs $(CONTAINER) && \
	docker-compose build --pull --no-cache --parallel $(CONTAINER) && \
	docker-compose up -d --force-recreate $(CONTAINER)

## Run the infrastructure tests for the docker setup
.PHONY: docker-test
docker-test: docker-init docker-up
	sh $(DOCKER_COMPOSE_DIR)/docker-test.sh

## Build all docker images.
## Build a specific image by providing the service name via: make docker-build CONTAINER=<service>
.PHONY: docker-build
docker-build: docker-init
	docker-compose build --parallel $(CONTAINER) && \
	docker-compose up -d --force-recreate $(CONTAINER)

## Remove unused docker resources via 'docker system prune -a -f --volumes'
.PHONY: docker-prune
docker-prune:
	docker system prune -a -f --volumes

## Start all docker containers.
## To only start one container, use CONTAINER=<service>
.PHONY: docker-up
docker-up: docker-init
	docker-compose up -d $(CONTAINER)

## Stop all docker containers.
## To only stop one container, use CONTAINER=<service>
.PHONY: docker-down
docker-down: docker-init
	docker-compose down $(CONTAINER)

# Build

.PHONY: build
build: bin
	sh bin/make_deb.sh clean

# Utilities

.SILENT: $(shell grep -h -E '^[a-zA-Z_-]+:.*?$$' $(MAKEFILE_LIST) | sort -u | awk 'BEGIN {FS = ":.*?"}; {printf "%s ", $$1}')

LIST_PAD=20
list:
	awk 'BEGIN {FS = ":.*##"; printf "Usage:\n  make \033[36m<target>\033[0m\n\nTargets:\n"}'
	grep -h -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort -u | awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[36m%-$(LIST_PAD)s\033[0m %s\n", $$1, $$2}'

PRE_PHP=XDEBUG_MODE=off

PHPUNIT_COMMAND="vendor/bin/paratest" -c $(PHPUNIT_CONFIG) --runner=WrapperRunner -p$(LOGICAL_CORES)
PHPUNIT_COVERAGE=php -d pcov.enabled=1 -d pcov.directory=./src $(PHPUNIT_COMMAND)

LOGICAL_CORES=$(shell nproc || sysctl -n hw.logicalcpu || echo 4)