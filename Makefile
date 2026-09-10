_: list

# Config

PHPCS_CONFIG=tools/phpcs.xml
PHPSTAN_SRC_CONFIG=tools/phpstan.neon
PHPSTAN_TESTS_CONFIG=tools/phpstan.tests.neon
PHPUNIT_CONFIG=tools/phpunit.xml
INFECTION_CONFIG=tools/infection.json

# QA

qa: ## Check code quality - coding style and static analysis
	make cs & make phpstan

cs: ## Check PHP files coding style
	mkdir -p var/tools/PHP_CodeSniffer
	$(PRE_PHP) "vendor/bin/phpcs" src --standard=$(PHPCS_CONFIG) --parallel=$(LOGICAL_CORES) $(ARGS)

csf: ## Fix PHP files coding style
	mkdir -p var/tools/PHP_CodeSniffer
	$(PRE_PHP) "vendor/bin/phpcbf" src --standard=$(PHPCS_CONFIG) --parallel=$(LOGICAL_CORES) $(ARGS)

lint:
	$(PRE_PHP) "vendor/bin/parallel-lint" src --exclude .git --exclude vendor

phpstan: ## Analyse code with PHPStan
	mkdir -p var/tools
	$(PRE_PHP) "vendor/bin/phpstan" analyse -c $(PHPSTAN_SRC_CONFIG) $(ARGS)
	$(PRE_PHP) "vendor/bin/phpstan" analyse -c $(PHPSTAN_TESTS_CONFIG) $(ARGS)

# TESTS

.PHONY: tests
tests: ## Run all tests
	$(PRE_PHP) $(PARATEST_COMMAND) $(ARGS)

tests-simple: ## Run all tests
	$(PRE_PHP) $(PHPUNIT_COMMAND) $(ARGS)

coverage-clover: ## Generate code coverage in XML format
	$(PRE_PHP) $(PHPUNIT_COVERAGE) --coverage-clover=var/tools/Coverage/clover.xml $(ARGS)

coverage-html: ## Generate code coverage in HTML format
	$(PRE_PHP) $(PHPUNIT_COVERAGE) --coverage-html=var/tools/Coverage/html $(ARGS)

mutations: ## Check code for mutants
	make mutations-tests
	make mutations-infection

mutations-tests:
	mkdir -p var/tools/Coverage
	$(PRE_PHP) $(PHPUNIT_MUTATIONS) --coverage-xml=var/tools/Coverage/xml --log-junit=var/tools/Coverage/junit.xml

mutations-infection:
	$(PRE_PHP) vendor/bin/infection \
		--configuration=$(INFECTION_CONFIG) \
		--threads=$(LOGICAL_CORES) \
		--coverage=../var/tools/Coverage \
		--skip-initial-tests \
		$(ARGS)

# DOCKER

up:
	docker-compose up -d

down:
	docker-compose down

bash:
	docker-compose exec -u www-data application bash

bash-root:
	docker-compose exec -u 0 application bash

# UTILITIES

.SILENT: $(shell grep -h -E '^[a-zA-Z_-]+:.*?$$' $(MAKEFILE_LIST) | sort -u | awk 'BEGIN {FS = ":.*?"}; {printf "%s ", $$1}')

LIST_PAD=20
list:
	awk 'BEGIN {FS = ":.*##"; printf "Usage:\n  make \033[36m<target>\033[0m\n\nTargets:\n"}'
	grep -h -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort -u | awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[36m%-$(LIST_PAD)s\033[0m %s\n", $$1, $$2}'

PRE_PHP=XDEBUG_MODE=off

PARATEST_COMMAND="vendor/bin/paratest" -c $(PHPUNIT_CONFIG) --runner=WrapperRunner -p$(LOGICAL_CORES)
PHPUNIT_COMMAND="vendor/bin/phpunit" -c $(PHPUNIT_CONFIG)
PHPUNIT_COVERAGE=php -d pcov.enabled=1 -d pcov.directory=./src $(PARATEST_COMMAND)
PHPUNIT_MUTATIONS=php -d pcov.enabled=1 -d pcov.directory=./src $(PARATEST_COMMAND)

LOGICAL_CORES=$(shell nproc || sysctl -n hw.logicalcpu || echo 4)