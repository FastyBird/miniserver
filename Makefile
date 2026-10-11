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
#
# Order matters: make stops at the first non-zero recipe line. The cheap guards -- `layers`,
# `discriminators`, `naming` -- run first, so a violation of the dependency direction, a
# discriminator map or the naming rules is reported at once rather than after minutes of static
# analysis; `phpstan` follows, then `cs`. All five are expected to be green on `main`.
qa: ## Check code quality - coding style and static analysis
	make layers
	make discriminators
	make naming
	make phpstan
	make cs

# PHPCS_PATHS is every directory of first-party PHP outside vendor/ and tools/. Until #610 it
# was `src` alone, so the application-scope tests under tests/cases/application and the two
# bin/*.php launchers were never checked and had drifted from the standard unnoticed.
# tests/stubs is excluded in tools/phpcs.xml (the .stub files are PHPStan stubs, not PHP the
# standard applies to). phpcs only picks up *.php by default, so the extensionless shebang
# wrappers bin/fb-console and bin/fb-supervisor are not scanned; each is a one-line include of
# its .php sibling, which is.
PHPCS_PATHS=src tests bin

cs: ## Check PHP files coding style
	mkdir -p var/tools/PHP_CodeSniffer
	$(PRE_PHP) "vendor/bin/phpcs" $(PHPCS_PATHS) --standard=$(PHPCS_CONFIG) --parallel=$(LOGICAL_CORES) $(ARGS)

csf: ## Fix PHP files coding style
	mkdir -p var/tools/PHP_CodeSniffer
	$(PRE_PHP) "vendor/bin/phpcbf" $(PHPCS_PATHS) --standard=$(PHPCS_CONFIG) --parallel=$(LOGICAL_CORES) $(ARGS)

# ARGS is passed through like the other targets, so `make lint ARGS='-j 2'` sets the job count.
lint:
	$(PRE_PHP) "vendor/bin/parallel-lint" src --exclude .git --exclude vendor $(ARGS)

# Rector is configured in rector.php for one job only: converting PHPUnit's doc-block
# annotations to attributes across the test suites. It is not a general refactoring pass.
# Rector emits fully qualified names, which the coding standard rejects, so always follow
# a real run with `make csf`.
rector: ## Preview the PHPUnit annotation-to-attribute conversion
	$(PRE_PHP) "vendor/bin/rector" process --dry-run $(ARGS)

rectorf: ## Apply the PHPUnit annotation-to-attribute conversion, then fix coding style
	$(PRE_PHP) "vendor/bin/rector" process $(ARGS)
	$(MAKE) csf

# Epic E5 (#460) runs its repository-wide mechanical rewrites through a committed Rector config
# per PR, tools/rector/e5-<topic>.php, copied from tools/rector/skeleton.php (which applies no
# rule). Unlike `make rector`, this applies the config; pass ARGS=--dry-run to preview. Run it in
# the application image, then `make csf`. RECTOR_CONFIG is required: without it Rector would
# fall back to the repository-root rector.php, which is the unrelated PHPUnit conversion.
# Epic E7 (#462) runs its two configs through the same target, limited to a type or a package by
# E7_PACKAGE: `E7_PACKAGE=Plugin make rector-e5 RECTOR_CONFIG=tools/rector/e7-override.php`
# (and tools/rector/e7-readonly.php); without E7_PACKAGE e7-override.php covers all 29 packages' src
# (Core included) and e7-readonly.php the 28 outside Core.
# The target always passes --clear-cache, for dry runs and for applying alike. Rector's cache
# (var/tools/Rector) can return stale results: in #713 a readonly run found 15 candidates instead
# of 20 until the cache was cleared. A cleared cache costs only speed, and a repository-wide
# rewrite runs a handful of times per PR, so a trustworthy result wins.
rector-e5: ## Apply an E5 Rector config: make rector-e5 RECTOR_CONFIG=tools/rector/e5-<topic>.php [ARGS=--dry-run]
	test -n "$(RECTOR_CONFIG)" || { echo "rector-e5: set RECTOR_CONFIG=tools/rector/<config>.php" >&2; exit 2; }
	test -f "$(RECTOR_CONFIG)" || { echo "rector-e5: $(RECTOR_CONFIG) does not exist" >&2; exit 2; }
	mkdir -p var/tools/Rector
	$(PRE_PHP) "vendor/bin/rector" process --config="$(RECTOR_CONFIG)" --no-progress-bar --clear-cache $(ARGS)

# Dependency-direction gate for the 29 packages under src/FastyBird. This is what replaced
# the per-package composer manifests (34 of them before the Core merge) as the statement of the
# dependency direction: they were measured to be fiction (117 undeclared edges against 149
# declared) and unenforceable by construction anyway.
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

# Like `layers`, plain PHP with no vendor/ dependency, so it runs on a bare checkout before
# `composer install`. Guards the invariant that every Doctrine inheritance root declares an
# explicit #[ORM\DiscriminatorMap]; without one, Doctrine's short-class-name default map is
# applied before the EntityDiscriminator subscriber contributes the real keys and every
# subtype lands in the map twice. That misbuild does not fail any test, because the value the
# database stores is still one of the two keys -- hence a gate rather than a test.
discriminators: ## Check every Doctrine inheritance root declares an explicit discriminator map
	$(PRE_PHP) php tools/check-discriminators.php $(ARGS)

# Like `layers` and `discriminators`, plain PHP with no vendor/ dependency, so it runs on a
# bare checkout before `composer install`. Guards the invariant that no file names a library
# fastybird/miniserver-core was assembled from -- in a namespace segment or a declared type
# name of any package, or in a `use FastyBird\Core\... as X` alias anywhere in the repository
# -- and no directory under any package's JS assets (src/FastyBird/<Type>/<Name>/assets) is
# named after one. It also refuses a Hungarian prefix on a declaration, in every package: an
# `interface I[A-Z]...` or a `trait T[A-Z]...` (check 6, #704).
#
# Aliases are the reason this is a gate rather than a review habit. There were 3,076 of them
# when the Core identity refactor started, in 131 distinct forms, and they existed purely
# because nothing checked. tools/naming-baseline.txt is empty now that every package has been
# reached; nothing may be added to it, and a stale entry fails the gate.
naming: ## Check no file names a library Core was assembled from, and no interface or trait carries an I or T prefix
	$(PRE_PHP) php tools/check-naming.php $(ARGS)

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

.SILENT: $(shell grep -h -E '^[a-zA-Z0-9_-]+:.*?$$' $(MAKEFILE_LIST) | sort -u | awk 'BEGIN {FS = ":.*?"}; {printf "%s ", $$1}')

LIST_PAD=20
list:
	awk 'BEGIN {FS = ":.*##"; printf "Usage:\n  make \033[36m<target>\033[0m\n\nTargets:\n"}'
	grep -h -E '^[a-zA-Z0-9_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort -u | awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[36m%-$(LIST_PAD)s\033[0m %s\n", $$1, $$2}'

PRE_PHP=XDEBUG_MODE=off

# Everything that runs the test suite loads one extra ini file on top of the
# image's own. It has to be an ini file rather than a phpunit.xml <php><ini>
# entry, and it has to travel through the environment, because the processes
# PHPUnit forks for @runTestsInSeparateProcesses inherit the environment but are
# started long before any PHPUnit configuration is applied. See
# tools/php.d/tests.ini for what is in it and why.
#
# The leading colon keeps the image's own scan directory; it does not replace it.
# FB_TEST_RUN_ID marks one suite run. tools/phpunit-bootstrap.php derives the temp and
# logs directories from it, so every paratest worker and every forked
# @runTestsInSeparateProcesses child of a single run share one compiled DI container,
# while the next run starts from a clean directory. Without the second half the
# container would never be rebuilt: debugMode is false under the test runner, so Nette
# uses ContainerLoader::loadOnce(), which never revalidates what it finds on disk.
PRE_PHP_TESTS=$(PRE_PHP) PHP_INI_SCAN_DIR=":$(CURDIR)/tools/php.d" FB_TEST_RUN_ID=$(shell date +%s)-$$$$

PARATEST_COMMAND="vendor/bin/paratest" -c $(PHPUNIT_CONFIG) --runner=WrapperRunner -p$(LOGICAL_CORES)
PHPUNIT_COMMAND="vendor/bin/phpunit" -c $(PHPUNIT_CONFIG)
PHPUNIT_COVERAGE=php -d pcov.enabled=1 -d pcov.directory=./src $(PARATEST_COMMAND)
PHPUNIT_MUTATIONS=php -d pcov.enabled=1 -d pcov.directory=./src $(PARATEST_COMMAND)

LOGICAL_CORES=$(shell nproc || sysctl -n hw.logicalcpu || echo 4)