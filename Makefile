_: list

# Config

PHPCS_CONFIG=tools/phpcs.xml
PHPSTAN_SRC_CONFIG=tools/phpstan.neon
PHPSTAN_TESTS_CONFIG=tools/phpstan.tests.neon
PHPUNIT_CONFIG=tools/phpunit.xml
INFECTION_CONFIG=tools/infection.json

# QA

# Two recipe lines, not `make cs & make phpstan`. The `&` backgrounded `make cs`,
# so this target's exit status was PHPStan's alone and a failing coding-standard
# check left `make qa` green -- with the two outputs interleaved, so the failure
# was easy to miss by eye as well. make stops on the first non-zero recipe line.
# CI was never affected (php-cs and php-phpstan are separate jobs), but this is
# the target a maintainer runs before pushing, so it is exactly the pre-push gate
# that did not gate.
qa: ## Check code quality - coding style and static analysis
	make cs
	make phpstan
	make layers

cs: ## Check PHP files coding style
	mkdir -p var/tools/PHP_CodeSniffer
	$(PRE_PHP) "vendor/bin/phpcs" src --standard=$(PHPCS_CONFIG) --parallel=$(LOGICAL_CORES) $(ARGS)

csf: ## Fix PHP files coding style
	mkdir -p var/tools/PHP_CodeSniffer
	$(PRE_PHP) "vendor/bin/phpcbf" src --standard=$(PHPCS_CONFIG) --parallel=$(LOGICAL_CORES) $(ARGS)

lint:
	$(PRE_PHP) "vendor/bin/parallel-lint" src --exclude .git --exclude vendor

# Dependency-direction gate for the 34 packages under src/FastyBird. This is what replaced
# the 34 per-package composer manifests, which were measured to be fiction (117 undeclared
# edges against 149 declared) and unenforceable by construction anyway.
#
# Note there is no "vendor/bin/" here and no `composer install` prerequisite: the checker is
# plain PHP with no dependency on vendor/, on an autoloader or on a framework, deliberately,
# so that it runs on a bare checkout. The CI step runs it before `composer install` to keep
# that a tested property rather than a claim in a comment.
#
# `make layers ARGS=--list-edges` prints the observed edge matrix without checking anything;
# use it to derive the rule for a newly added Bridge or Addon. It is local-only by design:
# because it checks nothing and always exits 0, the checker refuses to run it when CI is set
# in the environment, so it cannot be pasted into the CI step and turn the gate green.
layers: ## Check dependency direction between the packages under src/FastyBird
	$(PRE_PHP) php tools/check-layering.php $(ARGS)

phpstan: ## Analyse code with PHPStan
	mkdir -p var/tools
	$(PRE_PHP) "vendor/bin/phpstan" analyse -c $(PHPSTAN_SRC_CONFIG) $(ARGS)
	$(PRE_PHP) "vendor/bin/phpstan" analyse -c $(PHPSTAN_TESTS_CONFIG) $(ARGS)

# TESTS

.PHONY: tests
tests: ## Run all tests
	$(PRE_PHP_TESTS) $(PARATEST_COMMAND) $(ARGS)

tests-simple: ## Run all tests
	$(PRE_PHP_TESTS) $(PHPUNIT_COMMAND) $(ARGS)

coverage-clover: ## Generate code coverage in XML format
	$(PRE_PHP_TESTS) $(PHPUNIT_COVERAGE) --coverage-clover=var/tools/Coverage/clover.xml $(ARGS)

coverage-html: ## Generate code coverage in HTML format
	$(PRE_PHP_TESTS) $(PHPUNIT_COVERAGE) --coverage-html=var/tools/Coverage/html $(ARGS)

mutations: ## Check code for mutants
	make mutations-tests
	make mutations-infection

mutations-tests:
	mkdir -p var/tools/Coverage
	$(PRE_PHP_TESTS) $(PHPUNIT_MUTATIONS) --coverage-xml=var/tools/Coverage/xml --log-junit=var/tools/Coverage/junit.xml

mutations-infection:
	$(PRE_PHP_TESTS) vendor/bin/infection \
		--configuration=$(INFECTION_CONFIG) \
		--threads=$(LOGICAL_CORES) \
		--coverage=../var/tools/Coverage \
		--skip-initial-tests \
		$(ARGS)

composer-validate: ## Validate the root and every extension composer manifest
	# Deliberately NOT --strict, at either level. --strict promotes constraint-style
	# warnings to a non-zero exit, and three manifests carry such constraints that the
	# merge is forbidden to change: the root and Core/Tools declare mathsolver/mathsolver
	# as unbound (@dev), and the root and Connector/HomeKit pin endroid/qr-code to the
	# exact version 4.5. With --strict this target would be red from birth. Revisit in
	# Phase 6, when dependency changes are permitted.
	composer validate
	for manifest in src/FastyBird/*/*/composer.json; do \
		composer validate --no-check-lock "$$manifest" || exit 1; \
	done

# DOCKER

up:
	docker compose up -d

down:
	docker compose down

bash:
	docker compose exec -u www-data application bash

bash-root:
	docker compose exec -u 0 application bash

# UTILITIES

.SILENT: $(shell grep -h -E '^[a-zA-Z_-]+:.*?$$' $(MAKEFILE_LIST) | sort -u | awk 'BEGIN {FS = ":.*?"}; {printf "%s ", $$1}')

LIST_PAD=20
list:
	awk 'BEGIN {FS = ":.*##"; printf "Usage:\n  make \033[36m<target>\033[0m\n\nTargets:\n"}'
	grep -h -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort -u | awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[36m%-$(LIST_PAD)s\033[0m %s\n", $$1, $$2}'

PRE_PHP=XDEBUG_MODE=off

# Everything that runs the test suite loads one extra ini file on top of the
# image's own. It has to be an ini file rather than a phpunit.xml <php><ini>
# entry, and it has to travel through the environment, because the processes
# PHPUnit forks for @runTestsInSeparateProcesses inherit the environment but are
# started long before any PHPUnit configuration is applied. See
# tools/php.d/tests.ini for what is in it and why.
#
# The leading colon keeps the image's own scan directory; it does not replace it.
PRE_PHP_TESTS=$(PRE_PHP) PHP_INI_SCAN_DIR=":$(CURDIR)/tools/php.d"

PARATEST_COMMAND="vendor/bin/paratest" -c $(PHPUNIT_CONFIG) --runner=WrapperRunner -p$(LOGICAL_CORES)
PHPUNIT_COMMAND="vendor/bin/phpunit" -c $(PHPUNIT_CONFIG)
PHPUNIT_COVERAGE=php -d pcov.enabled=1 -d pcov.directory=./src $(PARATEST_COMMAND)
PHPUNIT_MUTATIONS=php -d pcov.enabled=1 -d pcov.directory=./src $(PARATEST_COMMAND)

LOGICAL_CORES=$(shell nproc || sysctl -n hw.logicalcpu || echo 4)