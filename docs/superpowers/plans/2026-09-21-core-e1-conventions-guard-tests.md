# Core Identity Refactor — E1: Conventions, Guard and Test Net

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Put in place the three things every later epic depends on — a gate that makes reintroducing a former library name impossible, a written convention document, and characterization tests over the Core public APIs that E5 is going to rewrite.

**Architecture:** One new plain-PHP guard (`tools/check-naming.php`) in the exact shape of the existing `check-layering.php` / `check-discriminators.php` guards: no vendor dependency, exit codes 0/1/2, a loud self-check so a broken matcher cannot report success. It carries a baseline of today's ~3,600 known violations which may only ever shrink — a stale baseline entry is itself a failure, which is what forces the shrink and doubles as the self-check. Alias legality is expressed as a positive rule rather than a denylist: an alias must equal the last two segments of the imported namespace. Characterization tests target only pure, dependency-free classes, so they run without MariaDB or Redis.

**Tech Stack:** PHP 8.4, PHPUnit 11 (via paratest), PHP_CodeSniffer with `orisai/coding-standard` 3.11.0, GNU Make, GitHub Actions.

**Spec:** `docs/superpowers/specs/2026-09-21-core-identity-refactor-design.md`

## Global Constraints

- **PHP 8.4**; Node 24; pnpm 10. The host's own PHP version does not count — every gate runs in the containers described in `docs/baseline.md`.
- **No live installation exists.** BC breaks are allowed. No deprecation shims, no compatibility aliases, no legacy code paths.
- **Never run `vendor/bin/paratest` directly.** Use `make tests`, which puts `tools/php.d/tests.ini` on `PHP_INI_SCAN_DIR`; without it 1,167 of 1,407 tests error.
- **There is no `vendor/bin/fb-console`.** Always `php bin/fb-console.php <command>`.
- **`composer validate --strict` exits 1** on two permanent warnings. Use `make composer-validate`.
- **Never pipe a gate through `tail`** — it reports `tail`'s exit status. Redirect to a file and check the command's own status.
- **Before trusting any green:** `rm -rf vendor/fastybird && composer install` (the mirror is a copy, not a symlink) and `rm -rf var/tools/PHPStan/` (a warm result cache reported clean locally while CI failed during PR #455).
- Test files must be named `*Test.php`. A `.phpt` suffix is invisible to PHPUnit, PHPCS and PHPStan simultaneously.
- Run gates in the application image, not `fastybird-php84-tools` — the tools image ships `memory_limit=128M` and both `make tests` and `make cs` die part-way under it.

## File Structure

| File | Responsibility |
|---|---|
| `tools/check-naming.php` | Create. The guard. Three detectors (namespace segments, declared type names, import aliases), baseline handling, self-check. |
| `tools/naming-baseline.txt` | Create. Sorted list of today's known violations. Shrinks each epic; deleted in E8. |
| `Makefile` | Modify. Add the `naming` target next to `discriminators`. |
| `.github/workflows/ci-tests.yaml` | Modify. Add a `make naming` step to the job that already runs `make layers` and `make discriminators`. |
| `docs/conventions.md` | Create. The written conventions from spec §5. |
| `CLAUDE.md` | Modify. Point at `docs/conventions.md` from the Conventions section. |
| `tools/phpcs.xml` | Modify. Ruleset 8.2 → 8.4; scope the one new rule. |
| `src/FastyBird/Core/Core/tests/cases/unit/Security/TokenTest.php` | Create. Characterization of JWT build/read/validate. |
| `src/FastyBird/Core/Core/tests/cases/unit/Messaging/ExchangeContainerTest.php` | Create. Characterization of publisher/consumer containers. |
| `src/FastyBird/Core/Core/tests/cases/unit/Http/ResponseTest.php` | Create. Characterization of the PSR-7 response helpers and `Stream`. |
| `src/FastyBird/Core/Core/tests/cases/unit/Routing/RouteParserTest.php` | Create. Characterization of URL generation and routing results. |
| `src/FastyBird/Core/Core/tests/cases/unit/Api/HydratorFieldsTest.php` | Create. Characterization of hydrator field value coercion. |
| `src/FastyBird/Core/Core/tests/cases/unit/WebSockets/FrameTest.php` | Create. Characterization of RFC6455 framing and the in-memory storages. |

---

## Task 1: The naming guard — detection

**Files:**
- Create: `tools/check-naming.php`

**Interfaces:**
- Consumes: nothing. Plain PHP, runs on a bare checkout with no `vendor/`.
- Produces: `php tools/check-naming.php` exits 1 and prints a sorted violation list to STDERR. Internally each violation is the string `<kind>\t<repo-relative path>\t<detail>`, where `<kind>` is one of `namespace`, `type`, `alias`; the STDERR *rendering* expands those tabs to spaces for readability. Task 2 consumes the tab-separated `$violations` array and the baseline file — **not** the STDERR text — so both sides stay tab-separated.

- [ ] **Step 1: Write the guard's detection core**

Create `tools/check-naming.php`:

```php
<?php declare(strict_types = 1);

/**
 * No file under src/FastyBird may name a library that fastybird/miniserver-core was
 * assembled from.
 *
 * WHY THIS IS A GATE
 *
 * Core was merged from 15 packages. The merge preserved their identities in six layers, and
 * the largest by far was import aliases: 3,333 `use FastyBird\Core\... as <OldName>;`
 * statements, 139 distinct forms, with FastyBird\Core\Exceptions alone aliased 11 different
 * ways depending on which library the importing file came from.
 *
 * They exist because nothing checked for them, and they will re-form without a gate, because
 * aliasing is the cheapest way to move a class without rewriting the file body. Renaming
 * directories does not fix this on its own: the alias is what a reader actually sees in the
 * body of the code.
 *
 * Aliases are checked by a POSITIVE rule rather than a denylist of old names. An alias is
 * legal only when it equals the last two segments of the imported namespace joined together:
 *
 *   use FastyBird\Core\Api\Schemas as ApiSchemas;      legal
 *   use FastyBird\Core\Documents as CoreDocuments;     legal
 *   use FastyBird\Core\Documents as ExchangeDocuments; ILLEGAL
 *
 * A denylist can only ban the names someone already thought of. The positive rule bans every
 * name that is not derived from where the symbol actually lives, which is the property we
 * actually want, and it needs no maintenance as capabilities are renamed.
 *
 * Namespaces and declared type names do use denylists, because there the offending token is a
 * known, closed set of former package names. The two lists differ on purpose: `Application`,
 * `Metadata` and `Tools` are banned as NAMESPACE SEGMENTS (they are former package names being
 * used as grouping layers) but permitted inside a declared TYPE name, where they are ordinary
 * English words -- Ratchet's WampApplication is not a reference to FastyBird:Application!.
 *
 * Exit codes follow tools/check-layering.php:
 *   0  clean
 *   1  at least one violation, or the baseline has gone stale
 *   2  the tool could not do its job
 *
 * Plain PHP, no dependencies; neither vendor/ nor an autoloader is required.
 */

/**
 * Bail out for reasons that are the TOOL's problem rather than the repository's, so a CI log
 * distinguishes "the invariant broke" from "the gate broke".
 */
function fbFail(string $message): never
{
	fwrite(STDERR, 'check-naming: ' . $message . PHP_EOL);

	exit(2);
}

const FB_NAMESPACE_DENYLIST = [
	'SimpleAuth',
	'SlimRouter',
	'DoctrineCrud',
	'DoctrineOrmQuery',
	'DoctrineTimestampable',
	'DoctrinePhone',
	'JsonApi',
	'JsonApiDocument',
	'Metadata',
	'Tools',
	'Application',
	'DateTimeFactory',
	'WebServer',
	'WsServer',
	'HttpServer',
	'IPub',
];

const FB_TYPE_DENYLIST = [
	'SimpleAuth',
	'SlimRouter',
	'DoctrineCrud',
	'DoctrineOrmQuery',
	'DoctrineTimestampable',
	'DoctrinePhone',
	'IPub',
	'IPublikuj',
];

$repoRoot = dirname(__DIR__);

if (!is_dir($repoRoot . '/src/FastyBird')) {
	fbFail(sprintf('"%s/src/FastyBird" is not a directory', $repoRoot));
}

/**
 * Every .php file in the repository that is ours: package sources and tests, plus the
 * root-level bootstrap and configuration files.
 *
 * The root-level ones matter. During PR #454 a consumer sweep scoped to src/FastyBird/**
 * missed public/index.php, which still referenced a namespace the sweep had removed; the
 * production Docker smoke test in CI was the only thing that caught it.
 *
 * @return array<string>
 */
function fbCollectFiles(string $repoRoot): array
{
	$files = [];

	$roots = [
		$repoRoot . '/src/FastyBird',
		$repoRoot . '/public',
		$repoRoot . '/bin',
		$repoRoot . '/tests',
		$repoRoot . '/migrations',
	];

	foreach ($roots as $root) {
		if (!is_dir($root)) {
			continue;
		}

		$iterator = new RecursiveIteratorIterator(
			new RecursiveCallbackFilterIterator(
				new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
				// pnpm materialises a node_modules per workspace package, each symlinking a
				// whole package root, so the same file is reachable many times over. Prune
				// them wherever they appear, not just at the repository root -- the same
				// reason tools/layering.php and tools/phpcs.xml do.
				static fn (SplFileInfo $entry): bool => $entry->getFilename() !== 'node_modules'
					&& $entry->getFilename() !== 'vendor',
			),
		);

		foreach ($iterator as $entry) {
			assert($entry instanceof SplFileInfo);

			if ($entry->isFile() && $entry->getExtension() === 'php') {
				$files[] = $entry->getPathname();
			}
		}
	}

	sort($files);

	return $files;
}

/**
 * @return array<string>
 */
function fbCheckFile(string $path, string $code, string $relative): array
{
	$violations = [];

	preg_match('/^namespace\s+([^;]+);/m', $code, $namespaceMatch);
	$namespace = isset($namespaceMatch[1]) ? trim($namespaceMatch[1]) : '';
	$isCore = str_starts_with($namespace, 'FastyBird\\Core');

	// 1. Namespace segments, Core only.
	if ($isCore) {
		foreach (explode('\\', $namespace) as $segment) {
			if (in_array($segment, FB_NAMESPACE_DENYLIST, true)) {
				$violations[] = sprintf("namespace\t%s\t%s in %s", $relative, $segment, $namespace);
			}
		}
	}

	// 2. Declared type names, Core only.
	if ($isCore) {
		preg_match_all(
			'/^(?:final\s+|abstract\s+|readonly\s+)*(?:class|interface|trait|enum)\s+(\w+)/m',
			$code,
			$typeMatches,
		);

		foreach ($typeMatches[1] as $typeName) {
			foreach (FB_TYPE_DENYLIST as $token) {
				if (str_contains($typeName, $token)) {
					$violations[] = sprintf("type\t%s\t%s contains %s", $relative, $typeName, $token);
				}
			}
		}
	}

	// 3. Import aliases of a FastyBird\Core symbol, everywhere in the repository.
	preg_match_all(
		'/^use\s+(FastyBird\\\\Core\\\\[A-Za-z0-9_\\\\]+)\s+as\s+(\w+)\s*;/m',
		$code,
		$aliasMatches,
		PREG_SET_ORDER,
	);

	foreach ($aliasMatches as $match) {
		$imported = $match[1];
		$alias = $match[2];
		$segments = explode('\\', $imported);
		$expected = implode('', array_slice($segments, -2));

		if ($alias !== $expected) {
			$violations[] = sprintf(
				"alias\t%s\t%s as %s (expected %s)",
				$relative,
				$imported,
				$alias,
				$expected,
			);
		}
	}

	return $violations;
}

$files = fbCollectFiles($repoRoot);
$violations = [];
$coreImports = 0;

foreach ($files as $path) {
	$code = file_get_contents($path);

	if ($code === false) {
		fbFail(sprintf('could not read "%s"', $path));
	}

	$relative = substr($path, strlen($repoRoot) + 1);
	$coreImports += preg_match_all('/^use\s+FastyBird\\\\Core\\\\/m', $code);

	foreach (fbCheckFile($path, $code, $relative) as $violation) {
		$violations[] = $violation;
	}
}

// Self-check. A regex that silently stops matching would otherwise report success over zero
// findings, which is the exact false-green this repository has been bitten by before. The
// floors are deliberately far below today's measured numbers (3,431 files in scope, 4,045
// FastyBird\Core imports) so ordinary churn does not trip them, while a broken matcher does.
if (count($files) < 2_000) {
	fbFail(sprintf('scanned only %d PHP files; expected at least 2000', count($files)));
}

if ($coreImports < 1_500) {
	fbFail(sprintf('found only %d FastyBird\\Core imports; expected at least 1500', $coreImports));
}

sort($violations);

if ($violations !== []) {
	fwrite(STDERR, sprintf("%d naming violations:\n\n", count($violations)));

	foreach ($violations as $violation) {
		fwrite(STDERR, '  ' . str_replace("\t", '  ', $violation) . PHP_EOL);
	}

	exit(1);
}

printf("Checked %d PHP files; no former library names in namespaces, type names or aliases.\n", count($files));

exit(0);
```

- [ ] **Step 2: Run it and confirm it reports a dirty repository**

Run: `php tools/check-naming.php > /tmp/naming.txt 2>&1; echo "exit=$?"`

Expected: `exit=1`, and the first line reports a four-digit violation count. Confirm the three
kinds are all present and the counts are in the right order of magnitude:

```bash
grep -c '^  alias'     /tmp/naming.txt   # expect ~3300
grep -c '^  namespace' /tmp/naming.txt   # expect ~300
grep -c '^  type'      /tmp/naming.txt   # expect ~1
```

- [ ] **Step 3: Verify the self-check actually fires (known-positive control)**

A guard that has never been seen to fail is not known to work. Break the file collector on
purpose and confirm the tool exits 2 rather than reporting a clean tree. The copy must live in
`tools/` so that `dirname(__DIR__)` still resolves to the repository root:

```bash
sed "s/\$entry->getExtension() === 'php'/\$entry->getExtension() === 'nope'/" \
  tools/check-naming.php > tools/check-naming-broken.php
grep -c "'nope'" tools/check-naming-broken.php     # MUST print 1, not 0
php tools/check-naming-broken.php; echo "exit=$?"
rm tools/check-naming-broken.php
```

Expected: the `grep -c` prints `1` — if it prints `0` the substitution silently did nothing and
the rest of this step proves nothing — then `exit=2` with
`check-naming: scanned only 0 PHP files; expected at least 2000`.

If it prints `exit=0` or `exit=1`, the self-check is not wired correctly. Fix it before
continuing.

- [ ] **Step 4: Confirm the alias rule through the guard's own code path**

Write a probe file containing one legal alias, one legal bare import and one violation, then run
the real guard over it rather than re-implementing the rule in a shell one-liner:

```bash
cat > src/FastyBird/Module/Devices/src/FbAliasProbe.php <<'PHP'
<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices;

use FastyBird\Core\Api\Schemas as ApiSchemas;
use FastyBird\Core\Documents as CoreDocuments;
use FastyBird\Core\Documents as ExchangeDocuments;
PHP
php tools/check-naming.php 2>&1 | grep FbAliasProbe
rm src/FastyBird/Module/Devices/src/FbAliasProbe.php
```

Expected: exactly one line, naming `ExchangeDocuments` and `(expected CoreDocuments)`. The two
legal imports must not appear. If `ApiSchemas` or `CoreDocuments` is reported, the
last-two-segments rule is implemented wrongly; if `ExchangeDocuments` is absent, the alias
detector is not matching at all.

- [ ] **Step 5: Commit**

```bash
git add tools/check-naming.php
git commit -m "build(tools): add the naming guard's detection core

Detects former library names in Core namespaces and declared type names,
and checks every FastyBird\\Core import alias against a positive rule: an
alias must equal the last two segments of the imported namespace.

Reports violations and exits 1; the baseline that makes it exit 0 on the
current tree arrives in the next commit.

Co-Authored-By: Claude Opus 5 (1M context) <noreply@anthropic.com>"
```

---

## Task 2: The naming guard — shrinking baseline

**Files:**
- Modify: `tools/check-naming.php`
- Create: `tools/naming-baseline.txt`

**Interfaces:**
- Consumes: the `<kind>\t<path>\t<detail>` violation lines produced by Task 1.
- Produces: `php tools/check-naming.php` exits 0 on the current tree. `php tools/check-naming.php --generate-baseline` rewrites `tools/naming-baseline.txt`. A violation absent from the baseline exits 1; a baseline line that is no longer violated also exits 1, with a message telling the engineer to remove it.

- [ ] **Step 1: Fix the namespace-boundary check carried over from Task 1**

Task 1's review found a latent false positive: `str_starts_with($namespace, 'FastyBird\\Core')`
also matches a namespace like `FastyBird\CoreExtra\Application`, which would then be scanned as
though it were Core. No such namespace exists today, so it is not an active bug — but a false
positive in a gate that will run across seven more epics is expensive, and the fix is one line.

In `fbCheckFile()`, change:

```php
	$isCore = str_starts_with($namespace, 'FastyBird\\Core');
```

to:

```php
	$isCore = $namespace === 'FastyBird\\Core' || str_starts_with($namespace, 'FastyBird\\Core\\');
```

Confirm it still finds the same violations as before the change:

```bash
php tools/check-naming.php 2>&1 | head -1
```

Expected: `3292 naming violations:` — the same count Task 1 reported. A *different* count means
the boundary change altered real detection rather than only the latent case, which is a finding,
not an improvement.

- [ ] **Step 2: Add baseline handling to the guard**

In `tools/check-naming.php`, replace everything from `sort($violations);` to the end of the file
with:

```php
sort($violations);

$baselinePath = $repoRoot . '/tools/naming-baseline.txt';

if (in_array('--generate-baseline', $argv, true)) {
	file_put_contents($baselinePath, implode(PHP_EOL, $violations) . PHP_EOL);

	printf("Wrote %d violations to tools/naming-baseline.txt.\n", count($violations));

	exit(0);
}

if (!is_file($baselinePath)) {
	fbFail('tools/naming-baseline.txt is missing; regenerate it with --generate-baseline');
}

$baselineRaw = file($baselinePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

if ($baselineRaw === false) {
	fbFail('could not read tools/naming-baseline.txt');
}

$baseline = array_values($baselineRaw);

$introduced = array_values(array_diff($violations, $baseline));
$fixed = array_values(array_diff($baseline, $violations));

if ($introduced !== []) {
	fwrite(STDERR, sprintf("%d NEW naming violations, not in the baseline:\n\n", count($introduced)));

	foreach ($introduced as $violation) {
		fwrite(STDERR, '  ' . str_replace("\t", '  ', $violation) . PHP_EOL);
	}

	fwrite(
		STDERR,
		PHP_EOL
		. "The baseline records what the Core identity refactor has not reached yet. It may\n"
		. "only ever shrink. See docs/conventions.md and the comment at the top of\n"
		. "tools/check-naming.php.\n",
	);

	exit(1);
}

// A baseline entry that no longer matches anything is a failure, not a courtesy. It is what
// forces the baseline to shrink as each epic lands, and it is also the real self-check: if the
// matcher breaks, every entry goes stale at once and the tool fails loudly instead of
// reporting a clean tree.
if ($fixed !== []) {
	fwrite(
		STDERR,
		sprintf("%d baseline entries are no longer violated. Remove them:\n\n", count($fixed)),
	);

	foreach ($fixed as $violation) {
		fwrite(STDERR, '  ' . str_replace("\t", '  ', $violation) . PHP_EOL);
	}

	fwrite(STDERR, PHP_EOL . "Run: php tools/check-naming.php --generate-baseline\n");

	exit(1);
}

printf(
	"Checked %d PHP files; %d known violations remain in the baseline, no new ones.\n",
	count($files),
	count($baseline),
);

exit(0);
```

- [ ] **Step 3: Generate the baseline**

Run: `php tools/check-naming.php --generate-baseline`

Expected: `Wrote 3292 violations to tools/naming-baseline.txt.` — the same count Step 1 asserted.

- [ ] **Step 4: Run the guard clean**

Run: `php tools/check-naming.php; echo "exit=$?"`

Expected: `exit=0` and `Checked <N> PHP files; <M> known violations remain in the baseline, no new ones.`

- [ ] **Step 5: Verify the guard rejects a new violation**

```bash
printf '<?php declare(strict_types = 1);\n\nnamespace FastyBird\\Module\\Devices;\n\nuse FastyBird\\Core\\Documents as SlimRouterDocuments;\n' > src/FastyBird/Module/Devices/src/FbNamingProbe.php
php tools/check-naming.php; echo "exit=$?"
rm src/FastyBird/Module/Devices/src/FbNamingProbe.php
```

Expected: `exit=1`, reporting exactly one new violation naming `FbNamingProbe.php` and
`expected CoreDocuments`.

- [ ] **Step 6: Verify the guard rejects a stale baseline entry**

```bash
head -1 tools/naming-baseline.txt          # note which file it names
printf 'alias\tsrc/FastyBird/NoSuchFile.php\tFastyBird\\Core\\Documents as Bogus (expected CoreDocuments)\n' >> tools/naming-baseline.txt
php tools/check-naming.php; echo "exit=$?"
git checkout tools/naming-baseline.txt 2>/dev/null || php tools/check-naming.php --generate-baseline
```

Expected: `exit=1` with `1 baseline entries are no longer violated. Remove them:` naming
`NoSuchFile.php`.

- [ ] **Step 7: Commit**

```bash
git add tools/check-naming.php tools/naming-baseline.txt
git commit -m "build(tools): give the naming guard a shrink-only baseline

Records today's known violations so the guard exits 0 on the current tree
while still rejecting anything new. A baseline entry that is no longer
violated is itself a failure: that is what forces the baseline to shrink as
each epic lands, and it doubles as the self-check, because a broken matcher
makes every entry go stale at once rather than reporting a clean tree.

Co-Authored-By: Claude Opus 5 (1M context) <noreply@anthropic.com>"
```

---

## Task 3: Wire the guard into `make` and CI

**Files:**
- Modify: `Makefile`
- Modify: `.github/workflows/ci-tests.yaml`

**Interfaces:**
- Consumes: `tools/check-naming.php` from Task 2.
- Produces: `make naming`. Every later epic's verification protocol calls it.

- [ ] **Step 1: Add the Makefile target**

In `Makefile`, immediately after the `discriminators:` target block (which starts at line 69),
add:

```make
# Like `layers` and `discriminators`, plain PHP with no vendor/ dependency, so it runs on a
# bare checkout before `composer install`. Guards the invariant that no file names a library
# fastybird/miniserver-core was assembled from -- in a Core namespace segment, in a declared
# type name, or in a `use FastyBird\Core\... as X` alias anywhere in the repository.
#
# Aliases are the reason this is a gate rather than a review habit. There were 3,333 of them
# when the Core identity refactor started, in 139 distinct forms, and they existed purely
# because nothing checked. tools/naming-baseline.txt records the ones not yet reached; it may
# only shrink, and a stale entry fails the gate.
naming: ## Check no file names a library that Core was assembled from
	$(PRE_PHP) php tools/check-naming.php $(ARGS)
```

- [ ] **Step 2: Run it**

Run: `make naming > /tmp/make-naming.txt 2>&1; echo "exit=$?"; cat /tmp/make-naming.txt`

Expected: `exit=0`, with the same summary line as Task 2 Step 3.

- [ ] **Step 3: Add the CI step**

In `.github/workflows/ci-tests.yaml`, immediately after the `make discriminators` step (lines
48-49, before the blank line preceding `Composer install`), add:

```yaml
      - name: "make naming"
        run: "make naming"
```

- [ ] **Step 4: Confirm the workflow still parses**

Run: `python3 -c "import yaml,sys; d=yaml.safe_load(open('.github/workflows/ci-tests.yaml')); print('jobs:', list(d['jobs'].keys()))"`

Expected: the job list prints without a parse error.

- [ ] **Step 5: Commit**

```bash
git add Makefile .github/workflows/ci-tests.yaml
git commit -m "build(tools): run the naming guard from make and CI

Co-Authored-By: Claude Opus 5 (1M context) <noreply@anthropic.com>"
```

---

## Task 4: Write down the conventions

**Files:**
- Create: `docs/conventions.md`
- Modify: `CLAUDE.md`

**Interfaces:**
- Consumes: spec §5.
- Produces: `docs/conventions.md`, referenced from `CLAUDE.md` and from the guard's failure message (already written in Task 2).

- [ ] **Step 1: Write `docs/conventions.md`**

```markdown
# Coding conventions

These are enforced where enforcement is possible: `make cs` for docblocks and code shape,
`make naming` for identity, `make phpstan` for types. Where a rule is not machine-checkable it
says so.

## Identity

No file may name a library that `fastybird/miniserver-core` was assembled from. The 15 merged
packages were SimpleAuth, SlimRouter, DoctrineCrud, DoctrineOrmQuery, DoctrineTimestampable,
DoctrinePhone, JsonApi, JsonAPIDocument, WebSockets, WebSocketsWAMP, WsServerPlugin,
WebServerPlugin, MetadataLibrary, Tools, Exchange, DateTimeFactory and Application.

Functional and technical terms are fine, because they describe what a thing *is* rather than
which package it came from: `Http`, `WebSockets`, `Wamp`, `Server`, `Phone`, `Clock`,
`Exchange`.

Enforced by `make naming`, which checks three places: namespace segments under
`FastyBird\Core`, declared type names under `FastyBird\Core`, and `use FastyBird\Core\... as X`
aliases anywhere in the repository.

## Import aliases

Import without an alias where the bare name does not collide. On collision, the alias is the
last two segments of the imported namespace, joined:

```php
use FastyBird\Core\Api\Schemas as ApiSchemas;        // legal
use FastyBird\Core\Documents as CoreDocuments;       // legal
use FastyBird\Core\Documents as ExchangeDocuments;   // rejected by make naming
```

This is a positive rule, not a denylist. A denylist can only ban the names someone already
thought of; this bans every name not derived from where the symbol actually lives.

`tools/naming-baseline.txt` recorded **3,076 aliases violating this rule** when the convention
was written, in 131 distinct forms using 112 distinct alias names. `FastyBird\Core\Exceptions`
alone was aliased 11 different ways, one per library the importing file happened to come from:

```
538  as ApplicationExceptions      73  as DoctrineCrudExceptions     25  as ExchangeExceptions
121  as JsonApiExceptions          56  as DoctrineOrmQueryExceptions 11  as SimpleAuthExceptions
 33  as ToolsExceptions             8  as WebSocketsExceptions        6  as SlimRouterExceptions
  2  as PhoneExceptions             1  as Exceptions (redundant -- import it bare)
```

The baseline may only shrink. A stale entry in it fails the gate.

## Namespace layout

Core is **capability-first**, following Symfony's component convention:
`Security\`, `WebSockets\`, `Api\`, `Http\`, `Persistence\`, `Values\`, `Documents\`,
`Exchange\`, `Phone\`, `Clock\`, `Logging\`.

Layer names — `Middleware`, `Subscribers`, `Entities`, `Controllers` — appear only *inside* a
capability, never at the top level. Exceptions and events live inside their capability too;
only genuinely cross-cutting exceptions sit at the root.

## Docblocks

**No file header.** The licence is in `LICENSE.md`, the author in `composer.json`, and the
namespace supersedes `@package`. `@package`, `@subpackage`, `@author`, `@copyright`,
`@license`, `@since` and `@date` are rejected by `make cs`.

- `@var`, `@param`, `@return`: omit where they only restate a native type. Keep for array
  shapes, generics and `@template`.
- `@throws`: **required and load-bearing.** PHPStan verifies them; an unused one fails CI.
- Class docblocks: only where they say something the signature does not.

## Naming

- Interfaces: no `I` prefix. Prefer no interface at all until there is a second implementation
  or a DI substitution point; otherwise the `…Interface` suffix.
- Traits: no `T` prefix. The `…Trait` suffix.
- No stuttering: not `Middleware\JsonApi\JsonApi`, not `Services\Phone\Phone`.

## Code

- `final` by default. Drop it only for a class that is actually extended.
- No `Nette\SmartObject`. Typed properties make it redundant.
- `#[\Override]` on every genuine override.
- `readonly class` where every property is readonly.
- Typed class constants, enforced by `SlevomatCodingStandard.TypeHints.ClassConstantTypeHint`.
- Constructor property promotion, enforced by `SlevomatCodingStandard.Classes.RequireConstructorPropertyPromotion`.
```

- [ ] **Step 2: Point CLAUDE.md at it**

In `CLAUDE.md`, in the `## Conventions` section, after the existing Conventional-commits
sentence, add:

```markdown
Code conventions — identity rules, import aliases, docblocks, naming, PHP idiom — are in
[docs/conventions.md](./docs/conventions.md) and enforced by `make naming` and `make cs`. Read
it before adding a file to `src/FastyBird/Core/Core`.
```

- [ ] **Step 3: Commit**

```bash
git add docs/conventions.md CLAUDE.md
git commit -m "docs(core): write down the coding conventions

Co-Authored-By: Claude Opus 5 (1M context) <noreply@anthropic.com>"
```

---

## Task 5: Raise the PHPCS ruleset from 8.2 to 8.4

**Files:**
- Modify: `tools/phpcs.xml:9`

**Interfaces:**
- Consumes: nothing.
- Produces: `make cs` runs with `php_version=80400` and `ClassConstantTypeHint` active for
  `src/FastyBird/Core/Core/**` only. E2 relies on that rule being live for Core; E7 removes the
  exclusions package by package.

**Background the implementer needs:** `orisai/coding-standard` 3.11.0 is already locked and does
ship `ruleset-8.4.xml`. The entire difference between `ruleset-8.2.xml` and `ruleset-8.4.xml` is
two things: `<config name="php_version">` goes from `80200` to `80400`, and one rule is added,
`SlevomatCodingStandard.TypeHints.ClassConstantTypeHint` (introduced in the 8.3 ruleset; 8.4 adds
nothing further).

That one rule is not small here. PHP_CodeSniffer itself reports **1,633 untyped class constants across 602
files** and **zero** typed ones. **214 of them, in 39 Core files** (36 under `src/`, plus
`Http/IResponse.php` and two test fixtures). So the rule is switched
on for Core only, and the other 28 packages are excluded until E7 reaches them.

- [ ] **Step 1: Confirm the fallout before changing anything**

```bash
grep -rEc '^\s*(public|protected|private)?\s*(final\s+)?const\s+[A-Z_][A-Z0-9_]*\s*=' \
  --include='*.php' src/FastyBird/Core/Core/src | grep -v ':0$' | wc -l
```

Expected: around `36`. This is a crude regex, not PHP_CodeSniffer, so treat it as an
order-of-magnitude check only — the authoritative figure is what `make cs` reports in Step 3,
which is **214 findings in 39 files**. Stop only if this prints something wildly different.

- [ ] **Step 2: Switch the ruleset and scope the new rule**

In `tools/phpcs.xml`, change line 9 from:

```xml
    <rule ref="./vendor/orisai/coding-standard/src/ruleset-8.2.xml">
```

to:

```xml
    <rule ref="./vendor/orisai/coding-standard/src/ruleset-8.4.xml">
```

Then, immediately before the closing `</ruleset>`, add:

```xml
    <!-- The only rule ruleset-8.4 adds over ruleset-8.2 (it arrived in the 8.3 ruleset; 8.4
         adds nothing further). PHP_CodeSniffer reports 1,633 untyped class constants across
         602 files and zero typed ones; 214 of them, in 39 files, are Core's. So it is switched
         on for Core first and the remaining packages -- 1,419 findings across 563 files -- are
         excluded until E7 of the Core identity refactor reaches them. This list may only
         shrink; deleting its last entry deletes the block. -->
    <rule ref="SlevomatCodingStandard.TypeHints.ClassConstantTypeHint">
        <exclude-pattern>src/FastyBird/Addon/*</exclude-pattern>
        <exclude-pattern>src/FastyBird/Automator/*</exclude-pattern>
        <exclude-pattern>src/FastyBird/Bridge/*</exclude-pattern>
        <exclude-pattern>src/FastyBird/Connector/*</exclude-pattern>
        <exclude-pattern>src/FastyBird/Module/*</exclude-pattern>
        <exclude-pattern>src/FastyBird/Plugin/*</exclude-pattern>
    </rule>
```

- [ ] **Step 3: Confirm the scoping works**

```bash
rm -rf var/tools/PHP_CodeSniffer
make cs > /tmp/cs.txt 2>&1; echo "exit=$?"
grep -c 'ClassConstantTypeHint' /tmp/cs.txt
grep 'ClassConstantTypeHint' /tmp/cs.txt | grep -c 'src/FastyBird/Core/Core'
```

Expected: **`exit=2`** — PHP_CodeSniffer returns 2 when errors are present and 1 when there are
only warnings, so 2 is the correct red here. Expect **214 findings, all of them
`ClassConstantTypeHint`, all inside `src/FastyBird/Core/Core`**. They are fixed in E2, not here.

Strip ANSI colour codes before counting, or the greps will miss: PHPCS prints the sniff code on
the line after the message and wraps both in colour escapes.

```bash
sed -e 's/\x1b\[[0-9;]*m//g' /tmp/cs.txt > /tmp/cs-plain.txt
grep -oE '\([A-Za-z]+\.[A-Za-z.]+\)$' /tmp/cs-plain.txt | sort | uniq -c | sort -rn
```

Expected: a single line, `214 (SlevomatCodingStandard.TypeHints.ClassConstantTypeHint.MissingNativeTypeHint)`.

The cache removal matters: PHPCS caches per-file results in `var/tools/PHP_CodeSniffer`, and a
warm cache will not re-evaluate files against the changed ruleset.

- [ ] **Step 4: Record the expected failure so E2 can close it**

`make cs` is now red on Core's untyped constants, by design. This is the one point in E1 where
a gate is deliberately left failing, so state it in the commit message rather than hiding it.

Confirm nothing *else* broke — the `php_version` bump can change other sniffs' behaviour. Use
the ANSI-stripped file from Step 3:

```bash
grep -oE '\([A-Za-z]+\.[A-Za-z.]+\)$' /tmp/cs-plain.txt | sort | uniq -c | sort -rn
```

Expected: exactly **one** distinct sniff, `ClassConstantTypeHint`. If any *other* sniff appears,
fix those findings in this task — they are fallout from `php_version`, not from the new rule, and
they must not be carried into E2.

The baseline that makes this interpretable: on the commit immediately before this task, `make cs`
exits 0 with zero ERROR and zero WARNING lines. Anything you see afterwards is caused by this
change.

- [ ] **Step 5: Commit**

```bash
git add tools/phpcs.xml
git commit -m "build(tools): raise the PHPCS ruleset from 8.2 to 8.4

The whole difference is php_version 80200 -> 80400 plus one rule,
SlevomatCodingStandard.TypeHints.ClassConstantTypeHint, which arrived in the
8.3 ruleset. PHP_CodeSniffer reports 1,633 untyped class constants across
602 files and zero typed ones, so the rule is scoped to Core and the other
28 packages are excluded until E7 reaches them.

make cs is deliberately left failing, exit 2, on Core's 214 untyped
constants; E2 types them.

Co-Authored-By: Claude Opus 5 (1M context) <noreply@anthropic.com>"
```

---

## Task 6: Characterize the security token pipeline

**Files:**
- Create: `src/FastyBird/Core/Core/tests/cases/unit/Security/TokenTest.php`

**Interfaces:**
- Consumes: `FastyBird\Core\Security\SimpleAuth\{TokenBuilder,TokenReader,TokenValidator}`,
  `FastyBird\Core\Services\DateTimeFactory\FrozenClock`, `FastyBird\Core\Constants\Constants`.
- Produces: the behavioural contract E5 must preserve when it replaces `DateTimeFactory\Clock`
  with PSR-20 and removes `Nette\SmartObject`.

**Signatures the implementer needs, verified against the current source:**

```php
TokenBuilder::__construct(string $tokenSignature, string $tokenIssuer, DateTimeFactory\Clock $clock)
TokenBuilder::build(string $userId, array $roles, DateTimeImmutable|null $expiration = null): JWT\UnencryptedToken
TokenValidator::__construct(string $tokenSignature, string $tokenIssuer, DateTimeFactory\Clock $clock)
TokenValidator::validate(string $token): JWT\Token|null          // throws Exceptions\UnauthorizedAccess
TokenReader::__construct(TokenValidator $tokenValidator)
TokenReader::read(ServerRequestInterface $request): JWT\UnencryptedToken|null
FrozenClock::__construct(float|DateTimeInterface $timestamp, DateTimeZone|null $timeZone = null)
Constants::TOKEN_HEADER_NAME = 'authorization'
Constants::TOKEN_CLAIM_USER  = 'user'
Constants::TOKEN_CLAIM_ROLES = 'roles'
```

- [ ] **Step 1: Write the test**

```php
<?php declare(strict_types = 1);

namespace FastyBird\Core\Tests\Cases\Unit\Security;

use DateTimeImmutable;
use FastyBird\Core\Constants;
use FastyBird\Core\Security\SimpleAuth;
use FastyBird\Core\Services\DateTimeFactory;
use Lcobucci\JWT;
use React\Http\Message\ServerRequest;
use PHPUnit\Framework\TestCase;
use Throwable;

final class TokenTest extends TestCase
{

	private const SIGNATURE = 'g3xHbkELpMD9LRqW4WmJkHL7kz2bdNYAXaVDBmOxCZorqVGRFb';

	private const ISSUER = 'com.fastybird.miniserver';

	private const NOW = '2026-09-21T12:00:00+00:00';

	private function clock(string $at = self::NOW): DateTimeFactory\FrozenClock
	{
		return new DateTimeFactory\FrozenClock(new DateTimeImmutable($at));
	}

	/**
	 * @throws Throwable
	 */
	public function testBuiltTokenCarriesTheUserAndRoleClaims(): void
	{
		$builder = new SimpleAuth\TokenBuilder(self::SIGNATURE, self::ISSUER, $this->clock());

		$token = $builder->build('9b1d2b4e-0a1e-4a6a-9d3f-1f2e3d4c5b6a', ['administrator', 'user']);

		self::assertSame(
			'9b1d2b4e-0a1e-4a6a-9d3f-1f2e3d4c5b6a',
			$token->claims()->get(Constants\Constants::TOKEN_CLAIM_USER),
		);
		self::assertSame(
			['administrator', 'user'],
			$token->claims()->get(Constants\Constants::TOKEN_CLAIM_ROLES),
		);
		self::assertSame(self::ISSUER, $token->claims()->get(JWT\Token\RegisteredClaims::ISSUER));
	}

	/**
	 * @throws Throwable
	 */
	public function testIssuedAtComesFromTheClockNotTheWallClock(): void
	{
		$builder = new SimpleAuth\TokenBuilder(self::SIGNATURE, self::ISSUER, $this->clock());

		$token = $builder->build('9b1d2b4e-0a1e-4a6a-9d3f-1f2e3d4c5b6a', []);

		$issuedAt = $token->claims()->get(JWT\Token\RegisteredClaims::ISSUED_AT);
		self::assertInstanceOf(DateTimeImmutable::class, $issuedAt);
		self::assertSame(
			(new DateTimeImmutable(self::NOW))->getTimestamp(),
			$issuedAt->getTimestamp(),
		);
	}

	/**
	 * @throws Throwable
	 */
	public function testEveryTokenGetsADistinctIdentifier(): void
	{
		$builder = new SimpleAuth\TokenBuilder(self::SIGNATURE, self::ISSUER, $this->clock());

		$first = $builder->build('9b1d2b4e-0a1e-4a6a-9d3f-1f2e3d4c5b6a', []);
		$second = $builder->build('9b1d2b4e-0a1e-4a6a-9d3f-1f2e3d4c5b6a', []);

		self::assertNotSame(
			$first->claims()->get(JWT\Token\RegisteredClaims::ID),
			$second->claims()->get(JWT\Token\RegisteredClaims::ID),
		);
	}

	/**
	 * @throws Throwable
	 */
	public function testValidatorAcceptsATokenThisBuilderProduced(): void
	{
		$builder = new SimpleAuth\TokenBuilder(self::SIGNATURE, self::ISSUER, $this->clock());
		$validator = new SimpleAuth\TokenValidator(self::SIGNATURE, self::ISSUER, $this->clock());

		$token = $builder->build('9b1d2b4e-0a1e-4a6a-9d3f-1f2e3d4c5b6a', ['user']);

		$validated = $validator->validate($token->toString());

		self::assertInstanceOf(JWT\UnencryptedToken::class, $validated);
		self::assertSame(
			'9b1d2b4e-0a1e-4a6a-9d3f-1f2e3d4c5b6a',
			$validated->claims()->get(Constants\Constants::TOKEN_CLAIM_USER),
		);
	}

	/**
	 * @throws Throwable
	 */
	public function testValidatorRejectsADifferentSignature(): void
	{
		$builder = new SimpleAuth\TokenBuilder(self::SIGNATURE, self::ISSUER, $this->clock());
		$validator = new SimpleAuth\TokenValidator(
			'Nq7ZBvAaP2sXtYuEwR5cV8bN1mK4jH6gF9dS3aQ0zL',
			self::ISSUER,
			$this->clock(),
		);

		$token = $builder->build('9b1d2b4e-0a1e-4a6a-9d3f-1f2e3d4c5b6a', []);

		self::assertNull($validator->validate($token->toString()));
	}

	/**
	 * @throws Throwable
	 */
	public function testValidatorRejectsADifferentIssuer(): void
	{
		$builder = new SimpleAuth\TokenBuilder(self::SIGNATURE, 'com.example.other', $this->clock());
		$validator = new SimpleAuth\TokenValidator(self::SIGNATURE, self::ISSUER, $this->clock());

		$token = $builder->build('9b1d2b4e-0a1e-4a6a-9d3f-1f2e3d4c5b6a', []);

		self::assertNull($validator->validate($token->toString()));
	}

	/**
	 * @throws Throwable
	 */
	public function testValidatorRejectsAnExpiredToken(): void
	{
		$builder = new SimpleAuth\TokenBuilder(self::SIGNATURE, self::ISSUER, $this->clock());
		$validator = new SimpleAuth\TokenValidator(
			self::SIGNATURE,
			self::ISSUER,
			$this->clock('2026-09-21T14:00:00+00:00'),
		);

		$token = $builder->build(
			'9b1d2b4e-0a1e-4a6a-9d3f-1f2e3d4c5b6a',
			[],
			new DateTimeImmutable('2026-09-21T13:00:00+00:00'),
		);

		self::assertNull($validator->validate($token->toString()));
	}

	/**
	 * @throws Throwable
	 */
	public function testValidatorRejectsGarbage(): void
	{
		$validator = new SimpleAuth\TokenValidator(self::SIGNATURE, self::ISSUER, $this->clock());

		self::assertNull($validator->validate('not-a-jwt'));
	}

	/**
	 * @throws Throwable
	 */
	public function testReaderExtractsABearerToken(): void
	{
		$builder = new SimpleAuth\TokenBuilder(self::SIGNATURE, self::ISSUER, $this->clock());
		$validator = new SimpleAuth\TokenValidator(self::SIGNATURE, self::ISSUER, $this->clock());
		$reader = new SimpleAuth\TokenReader($validator);

		$token = $builder->build('9b1d2b4e-0a1e-4a6a-9d3f-1f2e3d4c5b6a', ['user']);

		$request = (new ServerRequest('GET', '/api/v1/devices'))
			->withHeader(Constants\Constants::TOKEN_HEADER_NAME, 'Bearer ' . $token->toString());

		$read = $reader->read($request);

		self::assertInstanceOf(JWT\UnencryptedToken::class, $read);
		self::assertSame($token->toString(), $read->toString());
	}

	/**
	 * @throws Throwable
	 */
	public function testReaderReturnsNullWithoutAnAuthorizationHeader(): void
	{
		$validator = new SimpleAuth\TokenValidator(self::SIGNATURE, self::ISSUER, $this->clock());
		$reader = new SimpleAuth\TokenReader($validator);

		self::assertNull($reader->read(new ServerRequest('GET', '/api/v1/devices')));
	}

	/**
	 * @throws Throwable
	 */
	public function testReaderIgnoresAHeaderWithoutTheBearerPrefix(): void
	{
		$validator = new SimpleAuth\TokenValidator(self::SIGNATURE, self::ISSUER, $this->clock());
		$reader = new SimpleAuth\TokenReader($validator);

		$request = (new ServerRequest('GET', '/api/v1/devices'))
			->withHeader(Constants\Constants::TOKEN_HEADER_NAME, 'Basic dXNlcjpwYXNz');

		self::assertNull($reader->read($request));
	}

}
```

- [ ] **Step 2: Run the test**

Run: `make tests ARGS="--filter TokenTest" > /tmp/t6.txt 2>&1; echo "exit=$?"; tail -30 /tmp/t6.txt`

Expected: `exit=0`, `OK (11 tests, ...)`.

`React\Http\Message\ServerRequest` is the PSR-7 implementation this repository's own tests
already use (see `src/FastyBird/Bridge/DevicesModuleUiModule/tests/cases/unit/Controllers/DataSourcesV1Test.php`),
and `react/http` is a direct requirement of Core. Do not add a PSR-7 dependency for a test —
`nyholm/psr7` is **not** installed.

If `testValidatorRejectsGarbage` errors instead of returning null, that is a **finding, not a
test bug**: record the actual behaviour in the test — add `use FastyBird\Core\Exceptions;` and
`self::expectException(Exceptions\UnauthorizedAccess::class)`, and only then, since an unused
import fails `make cs`
and note it in the commit message. A characterization test documents what the code does today,
not what it ought to do.

- [ ] **Step 3: Confirm the whole suite is still green**

Run: `make tests > /tmp/tests.txt 2>&1; echo "exit=$?"; tail -5 /tmp/tests.txt`

Expected: `exit=0`. (9 Redis-related errors are environmental in a sandbox without a Redis
service; compare against a run from before this task rather than assuming.)

- [ ] **Step 4: Commit**

```bash
git add src/FastyBird/Core/Core/tests/cases/unit/Security/TokenTest.php
git commit -m "test(core): characterize the security token pipeline

Pins the behaviour E5 must preserve when DateTimeFactory\\Clock is replaced
with PSR-20 and Nette\\SmartObject is removed: claim contents, clock-derived
issuedAt, per-token identifiers, and the four rejection paths.

Co-Authored-By: Claude Opus 5 (1M context) <noreply@anthropic.com>"
```

---

## Task 7: Characterize the exchange containers

**Files:**
- Create: `src/FastyBird/Core/Core/tests/cases/unit/Messaging/ExchangeContainerTest.php`

**Interfaces:**
- Consumes: `FastyBird\Core\Messaging\Exchange\Publisher\{Container,Publisher}`,
  `FastyBird\Core\Messaging\Exchange\Consumers\{Container,Consumer,Info}`.
- Produces: the registration/dispatch contract E3 must preserve when this capability moves to
  `FastyBird\Core\Exchange\`.

**Signatures the implementer needs, verified against the current source:**

```php
Publisher\Container::__construct(...)   // read the file; publisher list is injected
Publisher\Container::register(Publisher\Publisher $publisher): void
Publisher\Container::publish(...): void
Publisher\Container::reset(): void
Consumers\Container::register(Consumers\Consumer $consumer, ...): void
Consumers\Container::consume(...): void
Consumers\Container::enable(string $class): void
Consumers\Container::disable(string $class): void
Consumers\Container::reset(): void
Consumers\Info::__construct(...)
Consumers\Info::getRoutingKey(): ...
Consumers\Info::isEnabled(): bool
```

- [ ] **Step 1: Read the exact signatures before writing the test**

Run:

```bash
sed -n '1,200p' src/FastyBird/Core/Core/src/Messaging/Exchange/Publisher/Container.php
sed -n '1,200p' src/FastyBird/Core/Core/src/Messaging/Exchange/Consumers/Container.php
sed -n '1,120p' src/FastyBird/Core/Core/src/Messaging/Exchange/Consumers/Info.php
```

Write down the exact parameter and return types of `publish`, `consume`, `register`, `enable`
and `disable`. They are needed verbatim in Step 2 — the public method *names* above are
extracted from the source and correct, but their signatures were not, and must not be guessed.

- [ ] **Step 2: Write the test**

Cover exactly these behaviours, using anonymous classes implementing `Publisher\Publisher` and
`Consumers\Consumer` that record their calls into a public array:

1. `register()` then `publish()` reaches every registered publisher, in registration order.
2. `publish()` with no registered publisher does not throw.
3. `reset()` clears registrations: a subsequent `publish()` reaches nobody.
4. Registering the same publisher instance twice results in it being called twice, or once —
   whichever the code actually does. Assert the observed behaviour.
5. `Consumers\Container::consume()` reaches every registered consumer.
6. `disable()` on a consumer's class name stops it receiving `consume()`; `enable()` restores it.
7. `Consumers\Info::isEnabled()` and `getRoutingKey()` return what the constructor was given.

Follow the file layout of `TokenTest.php` from Task 6: `final class`, `declare(strict_types = 1)`,
namespace `FastyBird\Core\Tests\Cases\Unit\Messaging`, extending `PHPUnit\Framework\TestCase`
directly (no container needed), `self::assert*` rather than `$this->assert*`, and a `@throws`
tag on every method that can throw.

- [ ] **Step 3: Run the test**

Run: `make tests ARGS="--filter ExchangeContainerTest" > /tmp/t7.txt 2>&1; echo "exit=$?"; tail -30 /tmp/t7.txt`

Expected: `exit=0` with at least 7 tests.

- [ ] **Step 4: Commit**

```bash
git add src/FastyBird/Core/Core/tests/cases/unit/Messaging/ExchangeContainerTest.php
git commit -m "test(core): characterize the exchange publisher and consumer containers

Co-Authored-By: Claude Opus 5 (1M context) <noreply@anthropic.com>"
```

---

## Task 8: Characterize the HTTP message layer

**Files:**
- Create: `src/FastyBird/Core/Core/tests/cases/unit/Http/ResponseTest.php`

**Interfaces:**
- Consumes: `FastyBird\Core\Http\{Response,Stream,ScalarEntity,Entity,ServerResponse}`.
- Produces: the contract E3 must preserve when this capability moves to `FastyBird\Core\Http\`
  (it keeps its name but gains `Http\Message\`), and E5 must preserve when PSR-7 immutability
  is reworked.

**Public surface, extracted from the current source:**

```
Response    -> __construct, getBody, getHeader, getHeaderLine, getHeaders, getProtocolVersion,
               getReasonPhrase, getStatusCode, hasHeader, html, json, notFound, redirect, text,
               withAddedHeader, withBody, withHeader, withProtocolVersion, withStatus,
               withoutHeader, xml
Stream      -> __construct, __toString, close, detach, eof, fromBodyString, fromResourceUri,
               getContents, getMetadata, getSize, isReadable, isSeekable, isWritable, read,
               rewind, seek, tell, write
ScalarEntity-> __construct, from
Entity      -> __construct, getData
ServerResponse -> getAttribute, getAttributes, getEntity, hasAttribute, withAttribute, withEntity
```

- [ ] **Step 1: Read the exact signatures before writing the test**

Run:

```bash
sed -n '1,240p' src/FastyBird/Core/Core/src/Http/Response.php
sed -n '1,120p' src/FastyBird/Core/Core/src/Http/ScalarEntity.php
sed -n '1,100p' src/FastyBird/Core/Core/src/Http/ServerResponse.php
```

Note in particular whether `json`, `text`, `html`, `xml`, `notFound` and `redirect` are static
factories or instance methods returning a new instance, and what status code and `Content-Type`
each sets. Those are the assertions.

- [ ] **Step 2: Write the test**

Cover exactly these behaviours:

1. `json()` sets a JSON `Content-Type` and a body that decodes back to the input array.
2. `text()`, `html()` and `xml()` each set their own `Content-Type`.
3. `notFound()` produces status 404.
4. `redirect()` produces a 3xx status and a `Location` header holding the target.
5. **PSR-7 immutability:** `withHeader()`, `withStatus()`, `withBody()` and `withProtocolVersion()`
   each return a *new* instance and leave the original untouched. Assert both halves —
   `assertNotSame($response, $modified)` and that the original still reports its old value.
6. `withoutHeader()` removes a header that `withHeader()` added.
7. `withAddedHeader()` appends rather than replaces: `getHeader()` returns both values.
8. `Stream::fromBodyString('abc')` reports `getSize() === 3`, `getContents() === 'abc'`, and
   `__toString() === 'abc'` after `rewind()`.
9. `ServerResponse::withAttribute()` is immutable in the same way, and `getAttribute()` returns
   the stored value while `hasAttribute()` reports presence.

Follow the file layout established in Task 6.

- [ ] **Step 3: Run the test**

Run: `make tests ARGS="--filter ResponseTest" > /tmp/t8.txt 2>&1; echo "exit=$?"; tail -30 /tmp/t8.txt`

Expected: `exit=0` with at least 9 tests.

If any `with*()` method mutates in place rather than returning a new instance, that is a
**PSR-7 conformance bug and a real finding**. Record the actual behaviour in the test, and open
a GitHub issue rather than fixing it here — fixing it changes runtime behaviour and belongs in
E5, not in a task whose job is to write down what exists.

- [ ] **Step 4: Commit**

```bash
git add src/FastyBird/Core/Core/tests/cases/unit/Http/ResponseTest.php
git commit -m "test(core): characterize the HTTP response and stream layer

Co-Authored-By: Claude Opus 5 (1M context) <noreply@anthropic.com>"
```

---

## Task 9: Characterize routing and URL generation

**Files:**
- Create: `src/FastyBird/Core/Core/tests/cases/unit/Routing/RouteParserTest.php`

**Interfaces:**
- Consumes: `FastyBird\Core\Routing\{Router,RouteCollector,RouteParser,RoutingResults,Route}`.
- Produces: the contract E3 must preserve when `Routing\` folds into `Http\Routing\`.

**Public surface, extracted from the current source:**

```
RouteCollector -> map, get, post, put, patch, delete, options, any, group, getNamedRoute,
                  removeNamedRoute, lookupRoute, getRoutes, getPattern, addMiddleware,
                  setDefaultInvocationHandler, appendMiddlewareToDispatcher
RouteParser    -> __construct, urlFor, relativeUrlFor, fullUrlFor
Router         -> __construct, map, get, post, ..., group, urlFor, lookupRoute, getNamedRoute,
                  getBasePath, setBasePath, handle, addMiddleware, getIterator
RoutingResults -> __construct, getMethod, getRouteArguments, getRouteIdentifier,
                  getRouteStatus, getUri
Route          -> __construct, getName, setName, getPattern, getMethods, getIdentifier,
                  getArgument(s), setArgument(s), getCallable, addMiddleware, prepare, run, handle
```

- [ ] **Step 1: Read the exact construction requirements**

Run:

```bash
sed -n '1,140p' src/FastyBird/Core/Core/src/Routing/Router.php
sed -n '1,120p' src/FastyBird/Core/Core/src/Routing/RouteParser.php
sed -n '1,100p' src/FastyBird/Core/Core/src/Routing/RoutingResults.php
grep -n 'createRouter' -A40 src/FastyBird/Core/Core/src/Routing/AppRouter.php
```

`AppRouter::createRouter()` shows how the application itself builds a router; copy that wiring
rather than inventing it. Note what `Router::__construct` requires — a response factory, a
container, or neither.

- [ ] **Step 2: Write the test**

Cover exactly these behaviours:

1. A route mapped as `get('/api/v1/devices', $handler)` and named `devices.index` is retrievable
   via `getNamedRoute('devices.index')`.
2. `urlFor('devices.index')` returns `/api/v1/devices`.
3. `urlFor()` on a route with a placeholder — `/api/v1/devices/{id}` — substitutes the argument:
   `urlFor('devices.read', ['id' => '9b1d2b4e-0a1e-4a6a-9d3f-1f2e3d4c5b6a'])` returns the
   fully substituted path.
4. `urlFor()` appends query-string arguments when given a third parameter, if the signature
   read in Step 1 accepts one.
5. `getNamedRoute()` on an unknown name throws; assert the exact exception class the source
   throws, read in Step 1 — do not guess between `Exceptions\InvalidArgument` and
   `Exceptions\Runtime`.
6. `removeNamedRoute()` makes the name unresolvable afterwards.
7. A `group('/api/v1', ...)` prefixes the patterns of the routes declared inside it.
8. `setBasePath('/sub')` is reflected in `urlFor()` output.

Follow the file layout established in Task 6.

- [ ] **Step 3: Run the test**

Run: `make tests ARGS="--filter RouteParserTest" > /tmp/t9.txt 2>&1; echo "exit=$?"; tail -30 /tmp/t9.txt`

Expected: `exit=0` with at least 8 tests.

- [ ] **Step 4: Commit**

```bash
git add src/FastyBird/Core/Core/tests/cases/unit/Routing/RouteParserTest.php
git commit -m "test(core): characterize routing and URL generation

Co-Authored-By: Claude Opus 5 (1M context) <noreply@anthropic.com>"
```

---

## Task 10: Characterize the JSON:API hydrator fields

**Files:**
- Create: `src/FastyBird/Core/Core/tests/cases/unit/Api/HydratorFieldsTest.php`

**Interfaces:**
- Consumes: `FastyBird\Core\Persistence\JsonApi\Hydrators\Fields\{Field,TextField,NumberField,BooleanField,DateTimeField,BackedEnumField,ArrayField,MixedField,EntityField}`.
- Produces: the value-coercion contract E3 must preserve when this moves to
  `FastyBird\Core\Api\Hydrators\Fields\`.

**Why these classes:** they are the only part of the JSON:API capability that is pure — no
Doctrine entity manager, no schema container, no HTTP request. `Hydrator::hydrate()` and
`Container::findHydrator()` both need a live entity manager and belong in an integration test,
not here.

**Public surface, extracted from the current source:**

```
Field           -> __construct, getFieldName, getMappedName, isRequired, isWritable
TextField       -> __construct, getValue, isNullable
NumberField     -> __construct, getValue, isNullable
BooleanField    -> __construct, getValue, isNullable
DateTimeField   -> __construct, getValue, isNullable
BackedEnumField -> __construct, getValue, isNullable
ArrayField      -> __construct, getValue, isNullable
MixedField      -> __construct, getValue, isNullable
EntityField     -> __construct, getClassName, isNullable, isRelationship
```

- [ ] **Step 1: Read the constructor signatures**

Run:

```bash
sed -n '1,90p'  src/FastyBird/Core/Core/src/Persistence/JsonApi/Hydrators/Fields/Field.php
for f in TextField NumberField BooleanField DateTimeField BackedEnumField ArrayField MixedField; do
  echo "=== $f"
  sed -n '1,90p' "src/FastyBird/Core/Core/src/Persistence/JsonApi/Hydrators/Fields/$f.php"
done
```

`Field` is the parent; the subclasses each add a `getValue()` that coerces. Note the exact
constructor parameter order — the tests instantiate these directly.

- [ ] **Step 2: Write the test**

Cover exactly these behaviours, one test method per field type, using a `#[DataProvider]` in
the style of the existing `tests/cases/unit/Utilities/ValueTest.php`:

1. `TextField::getValue('  hello  ')` — assert whether it trims or not, whichever it does.
2. `TextField::getValue('')` with `isNullable() === true` — assert whether an empty string
   becomes `null` or stays `''`.
3. `NumberField::getValue('42')` returns int/float `42`, and `getValue('4.5')` returns `4.5`.
4. `NumberField::getValue('not a number')` — assert the exact exception class or the coerced
   value, whichever the source does.
5. `BooleanField::getValue()` for each of `true`, `false`, `'true'`, `'false'`, `'1'`, `'0'`,
   `1`, `0`.
6. `DateTimeField::getValue('2026-09-21T12:00:00+00:00')` returns a `DateTimeInterface` with
   that timestamp; an unparseable string returns `null` or throws — assert what it does.
7. `BackedEnumField::getValue()` with a valid backing value returns the enum case; with an
   invalid one returns `null` or throws. Use an enum the repository already has —
   `FastyBird\Core\Types\Metadata\DataType` — rather than declaring a fixture enum.
8. `ArrayField::getValue([1, 2, 3])` round-trips; `getValue('not an array')` behaves as the
   source says.
9. `Field::isRequired()` and `isWritable()` return the constructor arguments unchanged, and
   `getMappedName()` falls back to `getFieldName()` when no mapped name was given — or does
   not; assert the observed behaviour.

Follow the file layout established in Task 6.

- [ ] **Step 3: Run the test**

Run: `make tests ARGS="--filter HydratorFieldsTest" > /tmp/t10.txt 2>&1; echo "exit=$?"; tail -30 /tmp/t10.txt`

Expected: `exit=0` with at least 9 tests.

- [ ] **Step 4: Commit**

```bash
git add src/FastyBird/Core/Core/tests/cases/unit/Api/HydratorFieldsTest.php
git commit -m "test(core): characterize the JSON:API hydrator field coercion

Co-Authored-By: Claude Opus 5 (1M context) <noreply@anthropic.com>"
```

---

## Task 11: Characterize WebSocket framing and the in-memory storages

**Files:**
- Create: `src/FastyBird/Core/Core/tests/cases/unit/WebSockets/FrameTest.php`

**Interfaces:**
- Consumes: `FastyBird\Core\Encoding\WebSockets\RFC6455\{Frame,Message}`,
  `FastyBird\Core\Encoding\WebSockets\Validator`,
  `FastyBird\Core\Topics\WsServer\{Storage,Drivers\InMemory}`,
  `FastyBird\Core\Clients\WsServer\{Storage,Drivers\InMemory}`.
- Produces: the contract E3 must preserve when this capability moves to
  `FastyBird\Core\WebSockets\`. This is the largest capability (84 files) and the one with the
  least existing coverage, so it is also the riskiest E3 move.

**Public surface, extracted from the current source:**

```
RFC6455\Frame   -> __construct, addBuffer, applyMask, extractOverflow, generateMaskingKey,
                   getContents, getMaskingKey, getOpCode, getPayload, getPayloadLength,
                   getPayloadStartingByte, getRsv1, getRsv2, getRsv3, isCoalesced, isFinal,
                   isMasked, maskPayload, unMaskPayload
RFC6455\Message -> __construct, addFrame, count, getContents, getOpCode, getPayload,
                   getPayloadLength, isCoalesced
Validator       -> __construct, checkEncoding
Topics\Storage  -> __construct, addTopic, getTopic, hasTopic, removeTopic, getStorageId,
                   getIterator, setStorageDriver, setLogger
Clients\Storage -> __construct, addClient, getClient, hasClient, refreshClient, removeClient,
                   getIterator, setStorageDriver
Drivers\InMemory-> __construct, contains, delete, fetch, fetchAll, save
```

- [ ] **Step 1: Read the exact signatures**

Run:

```bash
sed -n '1,200p' src/FastyBird/Core/Core/src/Encoding/WebSockets/RFC6455/Frame.php
sed -n '1,140p' src/FastyBird/Core/Core/src/Encoding/WebSockets/RFC6455/Message.php
sed -n '1,120p' src/FastyBird/Core/Core/src/Topics/WsServer/Drivers/InMemory.php
sed -n '1,140p' src/FastyBird/Core/Core/src/Topics/WsServer/Storage.php
```

Note what `Frame::__construct` takes (payload, final flag, opcode?) and what `Storage`
requires — the driver is set via `setStorageDriver()`, so establish whether the constructor
takes one too.

- [ ] **Step 2: Write the test**

Cover exactly these behaviours:

1. `new Frame('Hello', true, Frame::OP_TEXT)` (use whatever the real opcode constant is called)
   reports `isFinal() === true`, `getPayload() === 'Hello'`, `getPayloadLength() === 5`,
   `isCoalesced() === true`.
2. `maskPayload()` then `unMaskPayload()` round-trips back to the original payload, and
   `isMasked()` is true in between.
3. `getContents()` of an unmasked text frame starts with the byte `0x81` (FIN + text opcode).
4. A frame built by feeding `getContents()` of another frame through `addBuffer()` in two
   chunks coalesces to the same payload — this is the fragmented-read path.
5. `extractOverflow()` returns the bytes beyond a single frame when `addBuffer()` was given two
   concatenated frames, and the first frame's payload is intact.
6. `Message::addFrame()` twice with two continuation frames yields `getPayload()` equal to the
   concatenation and `count() === 2`.
7. `Validator::checkEncoding('valid utf8', 'utf-8')` is true, and an invalid byte sequence
   (`"\xC3\x28"`) is false. Read the real signature first — the second parameter may be an
   opcode rather than an encoding name.
8. `Drivers\InMemory`: `save()` then `fetch()` round-trips, `contains()` reports presence,
   `delete()` removes, `fetchAll()` returns everything saved.
9. `Topics\Storage`: `addTopic()` then `hasTopic()` / `getTopic()`, `removeTopic()` removes,
   and `getIterator()` yields the added topics.

Follow the file layout established in Task 6.

- [ ] **Step 3: Run the test**

Run: `make tests ARGS="--filter FrameTest" > /tmp/t11.txt 2>&1; echo "exit=$?"; tail -30 /tmp/t11.txt`

Expected: `exit=0` with at least 9 tests.

- [ ] **Step 4: Commit**

```bash
git add src/FastyBird/Core/Core/tests/cases/unit/WebSockets/FrameTest.php
git commit -m "test(core): characterize WebSocket framing and the in-memory storages

The largest Core capability at 84 files and the one with no existing
coverage, so the riskiest move in E3.

Co-Authored-By: Claude Opus 5 (1M context) <noreply@anthropic.com>"
```

---

## Task 12: Full-gate verification and epic close-out

**Files:**
- Modify: `tools/naming-baseline.txt` (regenerate — the new test files add Core imports)

**Interfaces:**
- Consumes: everything from Tasks 1–11.
- Produces: a branch that E2 can fork from with every gate in a known state.

- [ ] **Step 1: Rebuild the vendor mirror**

The new test files import `FastyBird\Core` production namespaces. PHPUnit loads files under
`tests/` from `src/` directly, but the DI container test does not, so rebuild before believing
any result.

```bash
rm -rf vendor/fastybird && composer install
diff -rq src/FastyBird/Core/Core/src vendor/fastybird/miniserver-core/src && echo "mirror fresh"
```

Expected: `mirror fresh`.

- [ ] **Step 2: Clear both stale caches**

```bash
rm -rf var/tools/PHPStan var/tools/PHP_CodeSniffer var/temp/cache
```

- [ ] **Step 3: Run every gate separately, checking each exit status**

```bash
for g in layers discriminators naming lint composer-validate cs phpstan tests; do
  make "$g" > "/tmp/gate-$g.txt" 2>&1
  echo "$g exit=$?"
done
```

Expected: `exit=0` for `layers`, `discriminators`, `naming`, `lint`, `composer-validate`,
`phpstan` and `tests`; `exit=2` for `cs`, per the paragraph below.

`cs` is expected to **exit 2** — PHP_CodeSniffer's code for "errors present" — on Core's 214
untyped class constants and nothing else. That is Task 5's deliberate, documented state, closed
by E2. Confirm it is only that, stripping ANSI codes first:

```bash
sed -e 's/\x1b\[[0-9;]*m//g' /tmp/gate-cs.txt | grep -oE '\([A-Za-z]+\.[A-Za-z.]+\)$' | sort | uniq -c | sort -rn
```

Expected: one line, `214 (SlevomatCodingStandard.TypeHints.ClassConstantTypeHint.MissingNativeTypeHint)`.
Any other sniff is a regression from this epic and must be fixed before the epic closes.

- [ ] **Step 4: Regenerate the naming baseline**

The new test files add legal Core imports but may also add aliased ones. Regenerate so the
baseline reflects reality, then confirm it did not *grow*:

```bash
wc -l < tools/naming-baseline.txt          # note the number
php tools/check-naming.php --generate-baseline
wc -l < tools/naming-baseline.txt          # must be <= the number above
```

If it grew, the new tests introduced alias violations. Fix the tests — they are new code and
must meet the convention — rather than accepting a larger baseline.

- [ ] **Step 5: Confirm zero schema drift**

```bash
php bin/fb-console.php orm:schema-tool:update --dump-sql
```

Expected: `Nothing to update - your database is already in sync with the current entity metadata.`

- [ ] **Step 6: Production Docker build and smoke test**

This is the gate that catches stdout written before the DI container's `initialize()` runs, and
the only one that exercises the real container with every extension registered.

```bash
docker build -f docker/prod/Dockerfile -t fastybird-miniserver:e1 . > /tmp/docker-build.txt 2>&1
echo "build exit=$?"
```

Expected: `build exit=0`.

- [ ] **Step 7: Fold the naming guard into the pre-push aggregate, and comment the CI step**

Two polish items Task 3's review raised, held back so they would not land as an unreviewed
amendment to an already-reviewed commit.

`qa:` carries the comment *"the target a maintainer runs before pushing, so it is exactly the
pre-push gate that did not gate"* — a note left after an incident where it failed to catch
something. The invariant this whole program exists to protect should not be missing from it.
In `Makefile`, add `make naming` to the `qa:` recipe, after `make layers`:

```make
qa: ## Check code quality - coding style and static analysis
	make cs
	make phpstan
	make layers
	make naming
```

Leave `discriminators` alone — it is absent from `qa` too, but that is a pre-existing gap and
changing it is outside this epic.

Then, in `.github/workflows/ci-tests.yaml`, give the `make naming` step the same kind of
explanatory comment its `make layers` and `make discriminators` neighbours carry:

```yaml
      # Same shape as `make layers` and `make discriminators`: plain PHP, runs before
      # `composer install` so that staying dependency-free remains a tested property. Exit 2
      # means the gate broke (a self-check floor tripped), exit 1 means either a new naming
      # violation or a baseline entry that is no longer violated -- the baseline may only
      # shrink.
      - name: "make naming"
        run: "make naming"
```

Verify: `make qa` now invokes the guard (`make -n qa | grep check-naming` prints the recipe),
and the workflow still parses.

Finally, correct two stale figures that shipped in Tasks 1 and 3. Both comments say *"3,333
aliases, 139 distinct forms"*, which counted **every** aliased `FastyBird\Core` import,
including the ones the last-two-segments rule permits. The committed baseline measures the
violations: **3,076 aliases in 131 distinct forms**. A reader who compares the comment against
`wc -l tools/naming-baseline.txt` should not find them disagreeing.

In `tools/check-naming.php`, in the "WHY THIS IS A GATE" block, change:

```
 * the largest by far was import aliases: 3,333 `use FastyBird\Core\... as <OldName>;`
 * statements, 139 distinct forms, with FastyBird\Core\Exceptions alone aliased 11 different
```

to:

```
 * the largest by far was import aliases: 3,076 `use FastyBird\Core\... as <OldName>;`
 * statements, 131 distinct forms, with FastyBird\Core\Exceptions alone aliased 11 different
```

And in `Makefile`, in the `naming:` comment, change:

```
# Aliases are the reason this is a gate rather than a review habit. There were 3,333 of them
# when the Core identity refactor started, in 139 distinct forms, and they existed purely
```

to:

```
# Aliases are the reason this is a gate rather than a review habit. There were 3,076 of them
# when the Core identity refactor started, in 131 distinct forms, and they existed purely
```

Confirm no stale figure survives anywhere: `grep -rn '3,333\|139 distinct' tools/ Makefile docs/`
must print nothing.

- [ ] **Step 7: Commit and push**

```bash
git add tools/naming-baseline.txt
git commit -m "build(tools): refresh the naming baseline after E1's new tests

Co-Authored-By: Claude Opus 5 (1M context) <noreply@anthropic.com>"
git push -u origin HEAD
```

---

## Self-review notes

**Spec coverage.** Spec §5.4 (the `make naming` guard) → Tasks 1–3. §5.1–5.3 written down →
Task 4. §5.3's ruleset bump → Task 5. §6 E1's characterization tests → Tasks 6–11. §7's
verification protocol → Task 12, and each characterization task runs the full suite.

**Deliberately not covered here.** The spec's E1 bullet mentions characterization tests broadly;
`Persistence\` CRUD is excluded because `EntityCrud`/`EntityCreator` need a live Doctrine entity
manager, which makes them integration tests against MariaDB rather than the pure unit tests the
rest of this epic delivers. They belong in E3's `Persistence` subtask, where the capability is
being moved anyway and the integration harness is worth standing up once.

**Known deliberate red.** Task 5 leaves `make cs` failing, exit 2, on 214 findings. Task 12 Step 3 pins
exactly which findings are acceptable. E2 closes it. This is stated in three places so it cannot
be mistaken for a regression.

**Signature guessing.** Tasks 6 gives complete, verified code. Tasks 7–11 each begin with a step
that reads the real signatures, because the public method *names* were extracted from the source
but their parameter and return types were not, and writing plausible-looking test code against
guessed signatures is how a plan produces work that has to be thrown away.
