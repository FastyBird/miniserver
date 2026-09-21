# Core Identity Refactor Design

- **Date:** 2026-09-21
- **Status:** agreed with Adam Kadlec, all decisions closed
- **Implementation plans:** to be written per epic under `docs/superpowers/plans/`
- **Predecessors:** `2026-09-20-core-consolidation-design.md` (PR #453), namespace flattening PRs #454 and #455

## 1. Goal

`fastybird/miniserver-core` was assembled by merging 15 previously independent packages. The
merge preserved their identities: the package is still legible as a conglomerate of SimpleAuth,
SlimRouter, DoctrineCrud, DoctrineTimestampable, DoctrinePhone, DoctrineOrmQuery, JsonApi,
JsonAPIDocument, WebSockets, WebSocketsWAMP, WsServerPlugin, WebServerPlugin, MetadataLibrary,
Tools, Exchange, DateTimeFactory and Application.

Make Core read as one package that was designed as a single unit from the start. No file, class,
namespace, import alias, docblock, DI key or asset directory may name the library it came from.
At the same time, bring the code from the PHP 8.2 idiom it was frozen in to PHP 8.4, with 8.5 in
sight.

## 2. Constraints and assumptions

These are facts confirmed with the maintainer on 2026-09-21, not guesses.

- **No live installation exists anywhere.** There is no data to preserve and no upgrade path to
  support. The three existing migrations may be collapsed into one fresh baseline at the end.
- **Backwards compatibility breaks are allowed and expected.** No deprecation shims, no aliases
  kept for compatibility, no legacy code paths.
- **PHP 8.4 is the target**, 8.5 is the preparation target. The project was last written for 8.2.
- **The refactor scope is the whole repository**, with Core as the focus. The maintainer
  explicitly selected all four extension areas: Core PHP, Core JS assets, Core test coverage,
  and the DI/NEON surface, plus a deep pass over the other 28 sub-packages afterwards.
- Database table and discriminator names are already free of library names
  (`fb_security_policies`, `fb_security_tokens`, `access_token`, `refresh_token`). No schema
  rename is needed. The value of "no installation" is therefore not data migration but that
  `orm:schema-tool:update --dump-sql` can be used as a hard zero-drift gate after every step.

## 3. Current state, measured 2026-09-21

409 PHP files in `src/FastyBird/Core/Core/src`. 1,993 files across the repository reference the
`FastyBird\Core\` namespace. Library identity leaks through six distinct layers.

### 3.1 Directory and namespace segments

26 type buckets still carry a former-package name as their second segment:
`Security/SimpleAuth/`, `Middleware/SlimRouter/`, `Persistence/DoctrineCrud/`, `Types/Metadata/`,
`Helpers/Tools/`, `Entities/Phone/`, `Providers/DoctrineTimestampable/` and so on. Roughly 264
files sit under such a segment.

### 3.2 Import aliases — the largest and most visible layer

**3,333 aliased `use` statements** repository-wide resurrect the old names in the one place a
reader actually looks, the body of the code: 139 distinct alias forms, 120 distinct alias names.

| Occurrences | Statement |
|---|---|
| 711 | `use FastyBird\Core\Types\Metadata as MetadataTypes;` |
| 538 | `use FastyBird\Core\Exceptions as ApplicationExceptions;` |
| 276 | `use FastyBird\Core\Documents as ApplicationDocuments;` |
| 219 | `use FastyBird\Core\Helpers\Tools as ToolsHelpers;` |
| 124 | `use FastyBird\Core\Entities\Application\Mapping as ApplicationMapping;` |
| 99 | `use FastyBird\Core\Routing as SlimRouterRouting;` |
| 58 | `use FastyBird\Core\Mapping\DoctrineCrud\Attribute as IPubDoctrine;` |

They are also internally inconsistent: `FastyBird\Core\Exceptions` alone is aliased **11
different ways** depending on which library the importing file originally came from. Renaming
directories without addressing this achieves nothing a reader would notice.

### 3.3 Docblocks

Every file carries two docblocks, a 14-line file header and a class docblock — 818 in total.
`@package` holds **21 distinct former package names**, including third-party ones:

```
79  FastyBird:SimpleAuth!      69  iPublikuj:WebSockets!       64  iPublikuj:JsonAPIDocument!
78  FastyBird:Application!     45  iPublikuj:WebSocketsWAMP!   41  FastyBird:JsonApi!
45  FastyBird:Tools!           37  iPublikuj:SlimRouter!       35  iPublikuj:DoctrineCrud!
27  FastyBird:MetadataLibrary! 27  FastyBird:Exchange!         22  iPublikuj:DoctrineTimestampable!
19  FastyBird:WebServerPlugin!  9  iPublikuj:Phone!             8  FastyBird:WsServerPlugin!
 5  iPublikuj:DoctrineOrmQuery! 4  iPublikuj:DoctrinePhone!     3  FastyBird:DateTimeFactory!
 2  FastyBird:DevicesModule!    1  iPublikuj:Permissions!       5  (empty)
```

`@subpackage` holds ~40 values that mostly no longer match the real directory (`Objects`,
`common`, `Crud`, `PushMessages`, `Models`, `Protocols`). `@since` and `@date` are stale
throughout.

### 3.4 DI and configuration surface

`CoreExtension.php` is a single 1,587-line class. Config keys and service names carry library
names: `fbCore.simpleAuth`, `fbCore.jsonApi`, `fbCore.jsonApi.middlewares.jsonapi`. These appear
in `config/common.neon`, `config/defaults.neon` and 24 per-package `tests/common.neon` files.

### 3.5 JS assets

74 `.ts`/`.vue` files under `assets/{tools,application,metadata,websockets}` — the same library
names, plus the `@fastybird/miniserver-core` export map.

### 3.6 Code idiom

| Measure | Count |
|---|---|
| Interfaces with `I` prefix | 68 |
| Traits with `T` prefix | 9 |
| Files using `Nette\SmartObject` | 82 |
| Non-final, non-abstract classes | 168 |
| `final` classes | 115 |
| `abstract` classes | 14 |
| `readonly` classes | 20 (of 201 constructors) |
| `#[\Override]` attributes | **0** |
| `@throws` / `@param` / `@return` / `@var` tags | 481 / 345 / 179 / 126 |
| Test files in Core | **11** |

`tools/phpcs.xml` imports `orisai/coding-standard`'s **`ruleset-8.2.xml`**, so rules introduced
for 8.3 and 8.4 are not applied at all.

## 4. Target architecture

### 4.1 Taxonomy decision

Core moves from **type-first** (`Controllers/`, `Middleware/`, `Subscribers/`, `Helpers/`,
`Entities/`, `Types/`) to **capability-first**.

Rationale, and the answer to the maintainer's question about current best practice: "package by
feature, not by layer" is the prevailing guidance, and it is what the project's own stated
inspirations already do. Symfony's components are capability-first —
`Symfony\Component\Messenger\Middleware\`, `Symfony\Component\Security\Core\Authentication\Token\Storage\`,
`Symfony\Component\HttpKernel\EventListener\` — with layer names surviving only *inside* a
capability. Sylius follows the same shape (`Sylius\Component\<Domain>\{Model,Repository,Factory}`).
Layer-first directory layout (`src/Controller`, `src/EventListener`) is Symfony's *application
bundle* skeleton convention; it was never the convention for a framework-core library, which is
what Core is.

### 4.2 Capability map

| Capability | Absorbs | Files |
|---|---|---|
| `Security\` | SimpleAuth in full: Access, Token, Identity, Casbin, Latte, Owner mapping, `Compat\User` | 45 |
| `WebSockets\` | WebSockets + WebSocketsWAMP + WsServerPlugin: protocol, WAMP, server, clients, topics | ~84 |
| `Api\` | JsonApi + JsonAPIDocument: Encoding, Objects, Hydrators, Schemas, Middleware | 55 |
| `Http\` | SlimRouter + WebServerPlugin: Message, Routing, Middleware, Server | 50 |
| `Persistence\` | DoctrineCrud + DoctrineOrmQuery + DoctrineTimestampable + object-mapper Rules | 41 |
| `Values\` | Tools + MetadataLibrary: DataType, Payloads, Sources, Formats, Transformers, Validator | 28 |
| `Documents\` | Application documents + mapping driver | 23 |
| `Exchange\` | Exchange: Publisher, Consumers, Factory | 13 |
| `Phone\` | DoctrinePhone: entity, type, service, subscriber | 5 |
| `Clock\` | DateTimeFactory, reshaped towards PSR-20 | 3 |
| `Logging\` | Tools Logger and Sentry helpers | 2 |
| `Exceptions\` | shared root only: `Exception`, `InvalidState`, `InvalidArgument`, `Logic`, `Runtime` | ~5 |

The file counts are **planning estimates, not a census**. They allocate the 36 files of the flat
`Events/` bucket and the 41 of `Exceptions/` to capabilities by inspection, and they do not sum to
409 because the runtime odds and ends dissolved at the root (~14) and a residue of roughly 40
events and exceptions have not yet been assigned to a specific owner. The exact per-capability
census is the first subtask of E3 and may move individual files between capabilities; it will not
change the set of capabilities.

Naming decisions taken deliberately, each because the original name is a former package name:
`Api\` not `JsonApi\`; `Values\` not `Metadata\`; and the runtime odds and ends stay **dissolved
at the root** (`Core\Boot\`, `Core\DI\`, `Core\Caching\`, `Core\EventLoop\`, `Core\Presenters\`,
`Core\UI\`) rather than being gathered under an invented umbrella — they are unrelated to each
other, and `Application\` is precisely the former `FastyBird:Application!` package name.

### 4.3 Two structural rules

1. **Exceptions and events live inside their capability**, following Symfony:
   `Security\Exceptions\UnauthorizedAccess`, `Http\Events\ServerRequest`,
   `Exchange\Events\MessagePublished`. Only genuinely cross-cutting exceptions stay at the root.
   This deliberately unpicks the flat `Exceptions/` (41) and `Events/` (36) buckets that PR #455
   created — an accepted cost of the taxonomy change.
2. **Layer names appear only inside a capability.** The global `Middleware/`, `Subscribers/`,
   `Helpers/`, `Controllers/`, `Providers/`, `Transformers/` buckets disappear entirely.

## 5. Conventions

### 5.1 Docblocks

- **The 14-line file header is removed from every file.** Licence lives in `LICENSE.md`, author
  in `composer.json`, and the namespace supersedes `@package`.
- Enforcement is free: `tools/phpcs.xml` today explicitly *excludes*
  `SlevomatCodingStandard.Commenting.ForbiddenAnnotations.AnnotationForbidden`. Removing that
  exclusion makes `make cs` reject any reintroduction of `@package`/`@author`/`@copyright`
  permanently, at zero maintenance cost.
- `@var`, `@param`, `@return`: deleted where they only restate a native type. Kept for array
  shapes, generics and `@template`.
- `@throws`: **kept and corrected.** These are load-bearing here — PHPStan verifies them, and
  `throws.unusedType` findings broke CI during PR #455.
- Class docblocks: kept only where they say something the signature does not.

### 5.2 Naming

- **`I`-prefix interfaces (68):** where an interface has exactly one implementation and is not a
  DI substitution point, the interface is deleted. Otherwise it is renamed to the `…Interface`
  suffix. Estimated two-thirds are removable; the exact split is determined per capability.
- **`T`-prefix traits (9):** renamed to the `…Trait` suffix.
- **Stuttering introduced by the merge is removed:** `Middleware\JsonApi\JsonApi`,
  `Schemas\JsonApi\JsonApi`, `Helpers\DoctrineCrud\Helpers`, `Services\Phone\Phone`,
  `Entities\Phone\Phone`, `Types\Phone\Phone`, `Presenters\SimpleAuth\TSimpleAuth`.
- **Import aliases:** no alias where the bare name does not collide. On collision, the alias is
  the last two namespace segments (`Api\Schemas` → `ApiSchemas`). A former package name is never
  an alias.

### 5.3 Code

`final` by default (168 candidates) · `Nette\SmartObject` removed (82 files) · `#[\Override]` on
every genuine override (0 today) · `readonly class` where every property is readonly · typed
class constants · enums in place of string constants where applicable · `orisai/coding-standard`
ruleset raised from 8.2 to 8.4.

**Resolved 2026-09-21.** `orisai/coding-standard` 3.11.0 is already locked and does ship
`ruleset-8.4.xml`. The entire difference from `ruleset-8.2.xml` is `php_version` 80200 → 80400
plus one added rule, `SlevomatCodingStandard.TypeHints.ClassConstantTypeHint` (introduced in the
8.3 ruleset; 8.4 adds nothing further). No dependency upgrade is needed.

That one rule is not small here: the repository has **1,636 untyped class constants across 605
files and zero typed ones**, 212 of them in 37 Core files. It is therefore switched on for Core
in E1 and excluded for the other 28 packages until E7 reaches them — a second list that may only
shrink, alongside the naming baseline.

### 5.4 Enforcement — `make naming`

A new `tools/check-naming.php`, in the same shape as the existing `check-layering.php`,
`check-discriminators.php` and `check-lsp.php` guards, with a `make naming` target. It rejects a
denylist of tokens (`SimpleAuth`, `SlimRouter`, `DoctrineCrud`, `DoctrineOrmQuery`,
`DoctrineTimestampable`, `DoctrinePhone`, `IPub`, `iPublikuj`, `Metadata`, `Tools`,
`JsonApiDocument`, `DateTimeFactory`, `WebServerPlugin`, `WsServerPlugin`) in three places:
namespaces, class names, **and `use … as` aliases**.

The third is the point. The 3,333 aliases exist precisely because nothing checked for them, and
they will re-form without a gate, because aliasing is the cheapest way to move a class without
rewriting the file body. The guard ships with a baseline file that shrinks with each epic and is
deleted in E8.

Functional and technical terms (`Http`, `WebSockets`, `Wamp`, `Server`, `Phone`, `Clock`) are
explicitly permitted — they describe what a thing *is*, not which package it came from.

## 6. Epics

Eight epics, roughly 45 subtasks. One PR per subtask in E3 and E5; one PR per epic elsewhere.

### E1 — Conventions, guard and test net *(blocks everything)*

`tools/check-naming.php` + `make naming` + baseline · characterization tests for Core's public
API (11 test files for 409 classes today) · `docs/conventions.md` and a CLAUDE.md pointer · phpcs
ruleset bump.

### E2 — Docblocks and mechanical modernization *(409 files, no moves)*

Header removal · removal of the `ForbiddenAnnotations` exclusion · `@var`/`@param`/`@return`/
`@throws` cleanup · `final` · `#[\Override]` · `readonly` · typed constants · `SmartObject`
removal.

**Ordered before E3 deliberately.** Removing a 14-line header is 20–25% of a small file's
content. Moving first would push many files past git's rename-detection threshold and they would
surface as delete-plus-create, making E3 unreviewable. Header removal first keeps every E3 move a
clean `git mv`.

### E3 — Capability restructure *(the core of the work, one PR per capability)*

Ordered by increasing risk rather than size, so the first three validate the mechanics —
including the consumer sweep — while the blast radius is small:

`Clock` (3) → `Logging` (2) → `Phone` (5) → `Values` (28) → `Documents` (23) → `Exchange` (13) →
`Persistence` (41) → `Api` (55) → `Http` (50) → `WebSockets` (84) → `Security` (45) → dissolve the
remainder into the root and dismantle the global `Exceptions/` and `Events/` buckets.

Each subtask is: move + rename that capability's `I`-prefix interfaces + rewrite consumers +
shrink the naming baseline. Interface *renaming* happens here because the consumer sweep is
already occurring; doing it separately would mean sweeping 1,993 files twice.

### E4 — DI and configuration

Split `CoreExtension.php` (1,587 lines, one class) into per-capability compiler extensions ·
rename NEON keys (`fbCore.simpleAuth` → `fbCore.security`, `fbCore.jsonApi` → `fbCore.api`) and
service names · update `config/common.neon`, `config/defaults.neon` and 24 `tests/common.neon`.

### E5 — Public API rewrite *(the "deep" option, per capability)*

Deletion of redundant interfaces · property hooks and asymmetric visibility replacing getter/setter
pairs · native abstractions replacing Nette and iPub ones (PSR-20 clock, PSR-11) · 8.5 preparation.

**Separated from E3 deliberately.** E3 is mechanical and provable by gates; E5 is semantic and
provable only by tests. Combined, a red gate cannot tell you which kind of change caused it — and
this repository has a documented history of false greens.

### E6 — JS assets *(74 files, runs in parallel with E3–E5)*

`assets/{tools,metadata,application,websockets}` renamed to capability names ·
`@fastybird/miniserver-core` export map · JS consumers.

### E7 — Roll the conventions out to the other 28 sub-packages

Headers, `I` prefixes, `SmartObject`, docblocks across Modules (4), Connectors (10), Bridges (6),
Plugins (5), Automators (2), Addon (1). One PR per extension type.

### E8 — Close-out

Collapse the three migrations into one fresh baseline · drive the naming baseline to zero and
delete the file · update `docs/architecture.md` · rewrite the Core README.

## 7. Verification protocol

Applied in every subtask. Each step exists because this repository has already been burned by its
absence.

1. `rm -rf vendor/fastybird && composer install` — `COMPOSER_MIRROR_PATH_REPOS=1` copies rather
   than symlinks path repos, so a cross-package move stales every mirror it touched.
2. `rm -rf var/tools/PHPStan/` — a warm PHPStan result cache reported clean locally while CI
   failed, during PR #455.
3. `make layers discriminators cs phpstan tests naming composer-validate` — each run separately,
   output to a file, checking *that command's* exit status. Never piped through `tail`, which
   reports `tail`'s status and has previously turned `make: *** Error 255` into a recorded
   success.
4. `orm:schema-tool:update --dump-sql` must report zero drift.
5. Production Docker build and smoke test at every epic boundary — the gate that catches
   pre-`initialize()` stdout regressions.

## 8. Risks

- **Thin test coverage against a deep rewrite.** Core has 11 test files for 409 classes. E5
  rewrites public APIs and replaces framework abstractions; neither `make cs` nor PHPStan can
  detect a behavioural regression there. E1's characterization tests are the mitigation and are
  a hard prerequisite for E5, not a nice-to-have. This risk was raised with the maintainer when
  the depth was chosen and accepted knowingly.
- **E3 unpicks merged work.** Moving exceptions and events into capabilities reverses part of
  PR #455. Accepted as the cost of the taxonomy change.
- **Consumer fan-out.** 1,993 files reference Core. Every E3 subtask touches consumers, and the
  history of this repo shows sweeps scoped to `src/FastyBird/**` miss root-level files
  (`public/index.php` broke the CI Docker smoke test during PR #454 for exactly this reason).
  Every sweep must cover the whole repository.
- **Program length.** Eight epics across ~2,100 files. The naming baseline is the mechanism that
  keeps partial progress honest: it can only shrink.
