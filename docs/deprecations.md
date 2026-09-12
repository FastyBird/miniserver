# Deprecated dependencies

Libraries this project still depends on that are unmaintained, abandoned upstream,
or otherwise known to be on their way out. Each entry records what it costs us, so
a replacement can be planned rather than forced by an incident.

Nothing here is urgent unless its "Blocks" line says so.

## orisai/object-mapper — unmaintained, caps PHP at 8.4

**Status:** last release `0.3.0`, 2025-01-21. The wider org has shipped nothing
since `orisai/nette-di` in December 2025; `orisai/utils` and `orisai/exceptions`
have not moved since December 2024.

**Blocks:** PHP 8.5. `orisai/object-mapper` and `orisai/nette-object-mapper`
declare `php: 7.4 - 8.4`, and six further `orisai/*` packages arrive transitively
with the same ceiling. PHP 8.4 is supported upstream into 2028, so this is a
medium-term constraint, not an immediate one.

**Footprint:** 497 files across 25 extensions import `Orisai\ObjectMapper`. It is
the only `Orisai\*` namespace used in application source — every other orisai
package is transitive, except `orisai/coding-standard`, which is tooling only.

    2481  #[ObjectMapper\...] attribute declarations
     652  ObjectMapper\Modifiers\FieldName
     193  classes implementing MappedObject
      74  runtime processor call sites

**Why native PHP is not a drop-in replacement.** Most of the *rules* are things
the type system already expresses — `AnyOf` + `NullValue` (1,951 uses combined) is
the nullable idiom, `StringValue`/`IntValue`/`BoolValue`/`FloatValue` (1,594) are
scalar types, and `BackedEnumValue` (187) is a PHP 8.1 backed enum. Roughly 3,700
of ~4,900 rule usages fall into that category.

What has no native equivalent is the rest: mapping JSON keys to property names
(652 uses of `FieldName`), validating array element types (`ArrayOf`,
`ArrayEnumValue`, 407 uses), constructing nested mapped objects
(`MappedObjectValue`, 329), and producing structured validation errors
(`InvalidData`, `ErrorVisualPrinter`). Writing those is writing a mapping library.

**Most promising replacement:** `cuyz/valinor`, because it maps from *types*
rather than from attributes. Most of the 2,481 attributes would be deleted rather
than translated, since `?string` already encodes what
`AnyOf([StringValue, NullValue])` encodes. The real work concentrates in the 652
`FieldName` modifiers and the 74 processor call sites.

**Cheap hedge, if wanted before committing to a migration:** funnel the 74 runtime
call sites through a thin internal wrapper. That turns a future swap into a
74-site change instead of a 497-file one.

## Abandoned Composer packages

Reported by the `Composer Audit` CI job on every run. None blocks anything today;
`composer audit` passes because the job runs `--abandoned=report`.

| package | suggested replacement |
|---|---|
| `doctrine/annotations` | none given |
| `doctrine/cache` | none given |
| `leigh/curve25519` | none given |
| `nette/safe` | none given |
| `nettrine/cache` | `symfony/cache` |
| `php-http/message-factory` | `psr/http-factory` |

Several arrive transitively through Doctrine and nettrine, so the Doctrine ORM 3
upgrade will likely retire some of them without direct action.

## PHP 8.4 deprecation notices from vendor

Requiring nothing but `vendor/autoload.php` on PHP 8.4 emits **258** `E_DEPRECATED`
notices, every one of them "Implicitly marking parameter `$x` as nullable is
deprecated". They come from files composer loads eagerly through `autoload.files`,
so they fire before any application code runs. Nothing fails on them: they are
notices, and PHPStan reports `[OK] No errors` with all 258 present.

| package | notices | reached through | scope |
|---|---|---|---|
| `thecodingmachine/safe` v2.5.0 | 256 | `infection/infection` 0.27.11 | dev only |
| `illuminate/support` v9.52.16 | 2 | `mathsolver/mathsolver` | production |

The 256 retire themselves with the Infection upgrade that PHP 8.2 was blocking:
Infection's current release (0.35.4) requires `php: ^8.3` and
`thecodingmachine/safe: ^3`, and safe 3 fixed the implicit nullables.

The remaining 2 (`optional()` and `with()` in `illuminate/support/helpers.php`) are
production and have no such path. Laravel 9 is end of life, and
`mathsolver/mathsolver` -- required by `Core/Tools`, from a git repository rather
than Packagist -- is also the package behind one of the two permanent
`composer validate` warnings. Worth its own look when the dependency upgrades reach
it.

## yarn 1

Unmaintained upstream. The migration to pnpm is planned as its own track and is
not blocked by anything here.
