<?php declare(strict_types = 1);

/**
 * E3.13 (#506): Security.
 *
 * Written by hand from the approved census, section "Security (47 files)" of
 * docs/superpowers/plans/2026-09-23-core-e3-capability-census.md, plus the name table
 * (`IAuthenticator` -> `Identity\Authenticator`, `IIdentity` -> `Identity\UserIdentity`,
 * `IIdentityFactory` -> `Identity\IdentityProvider` -- the last two are policy-B renames the
 * census itself found: a bare I-drop on `IIdentityFactory` would collide with the concrete
 * `IdentityFactory`, and a bare I-drop on `IIdentity` would stutter against the `Identity\`
 * sub-namespace this bucket collapses into), the stutter table (`Entities\SimpleAuth\TOwner`
 * -> `Security\Entities\HasOwner`, `Presenters\SimpleAuth\TSimpleAuth` ->
 * `Security\Presenters\HasAuthorization`), the collision table's `User` row (the four
 * `User`-named files in `Security` resolve to `Security\Identity\User`,
 * `Security\Middleware\User` and `Security\Subscribers\User` -- all three unchanged short
 * names, disambiguated by sub-namespace the same way the tool already aliases any reference
 * against `Nette\Security\User` or a module `User`; the fourth, `Compat\User.php`, is NOT
 * part of this map, see below), and the Events/Exceptions assignment (`Authentication`,
 * `ForbiddenAccess`, `UnauthorizedAccess` -> `Security\Exceptions\*`). 47 files, all class/
 * interface/trait/enum moves (this map's `classes` key).
 *
 * - `Entities\SimpleAuth\*` (4) -> `Security\Entities\*` -- `Owner` interface unchanged,
 *   `Policies\Policy` and `Tokens\Token` unchanged, `TOwner` -> `HasOwner` (stutter table;
 *   the OTHER `TOwner`, `Documents\TOwner` -> `Documents\HasOwner`, moved with Documents in
 *   #500 and is untouched here).
 * - `Exceptions\{Authentication,ForbiddenAccess,UnauthorizedAccess}` (3) -> `Security\
 *   Exceptions\*` (Events/Exceptions assignment).
 * - `Latte\SimpleAuth\*` (4) -> `Security\Latte\*` -- names unchanged.
 * - `Mapping\SimpleAuth\{Attribute,Driver}\Owner` (2) -> `Security\Mapping\{Attribute,
 *   Driver}\Owner` -- names unchanged, disambiguated from `Security\Entities\Owner` and
 *   `Documents\Owner` by sub-namespace (collision table).
 * - `Middleware\SimpleAuth\{Authorization,User}` (2) -> `Security\Middleware\*` -- names
 *   unchanged.
 * - `Persistence\SimpleAuth\Models\{Casbin,Policies,Tokens}\*` (6) -> `Security\Models\*` --
 *   names unchanged; `Models\Policies\*` and `Models\Tokens\*` disambiguated from each
 *   other and from module `Manager`/`Repository` pairs by sub-namespace (collision table).
 * - `Persistence\SimpleAuth\Queries\{FindPolicies,FindTokens}` (2) -> `Security\Queries\*`
 *   -- names unchanged.
 * - `Presenters\SimpleAuth\TSimpleAuth` -> `Security\Presenters\HasAuthorization` (stutter
 *   table, T-prefix checklist item).
 * - `Security\SimpleAuth\Access\*` (4) -> `Security\Access\*` -- names unchanged.
 * - `Security\SimpleAuth\{EnforcerFactory,IUserStorage,IdentityFactory,PlainIdentity,
 *   TokenBuilder,TokenReader,TokenValidator,User,UserStorage}` (9) -> `Security\Identity\*`
 *   -- names unchanged; `User` disambiguated from `Nette\Security\User`, `Security\
 *   Middleware\User` and `Security\Subscribers\User` by sub-namespace.
 * - `Security\SimpleAuth\IAuthenticator` -> `Security\Identity\Authenticator` (name table).
 * - `Security\SimpleAuth\IIdentity` -> `Security\Identity\UserIdentity` (name table).
 * - `Security\SimpleAuth\IIdentityFactory` -> `Security\Identity\IdentityProvider` (name
 *   table).
 * - `Services\SimpleAuth\Auth` -> `Security\Services\Auth` -- name unchanged.
 * - `Subscribers\SimpleAuth\{Application,Policy,User}` (3) -> `Security\Subscribers\*` --
 *   names unchanged; `User` disambiguated as above.
 * - `Types\SimpleAuth\{PolicyType,TokenState}` (2) -> `Security\Types\*` -- names unchanged.
 *
 * `Compat/User.php` is NOT part of this map. Census decision 2: it declares
 * `namespace Nette\Security;` and is dead, never-loaded code; #535 (E3.12, WebSockets)
 * already relocated the file to `src/WebSockets/Compat/User.php` on a file-only move, FQCN
 * untouched. The issue's "48 files including `Compat\User`" is the pre-#535 count; the
 * approved census counts 47 and already excludes it (see census row 66: "Security | 47 |
 * 48 | -1 (`Compat\User.php` physically moved out, decision 2)"). This PR therefore moves
 * exactly the 47 files in the census's "Security (47 files)" table and does not touch
 * `WebSockets/Compat/User.php`.
 *
 * No `normalize` entry. `FastyBird\Core\Security` already exists as a namespace (it holds
 * only `SimpleAuth\` today, see `git grep` before writing this map), and one file aliases it
 * illegally: `DI/CoreExtension.php` imports it as `SimpleAuthSecurity`. Every reference
 * through that import names a class this map moves, so after the rewrite the import carries
 * zero references and the tool's ordinary "unused import" path drops it -- a fresh,
 * correctly (two-segment, where needed) aliased import is added per target sub-namespace
 * instead, exactly as an unaliased import would be. Unlike `Http`/`Documents` (#500, #504),
 * nothing stays behind at the OLD `FastyBird\Core\Security\SimpleAuth\*` location for that
 * import to keep naming, so there is no illegal alias left over to normalize -- the same
 * outcome as WebSockets (#535, "no normalize entries"), reached for a different reason:
 * there the namespace was new, here every existing member of it moves out.
 *
 * Applied by tools/move-core-symbols.php; see that file for the format.
 */
return [
	'classes' => [
		'FastyBird\\Core\\Entities\\SimpleAuth\\Owner' => 'FastyBird\\Core\\Security\\Entities\\Owner',
		'FastyBird\\Core\\Entities\\SimpleAuth\\Policies\\Policy' => 'FastyBird\\Core\\Security\\Entities\\Policies\\Policy',
		'FastyBird\\Core\\Entities\\SimpleAuth\\TOwner' => 'FastyBird\\Core\\Security\\Entities\\HasOwner',
		'FastyBird\\Core\\Entities\\SimpleAuth\\Tokens\\Token' => 'FastyBird\\Core\\Security\\Entities\\Tokens\\Token',
		'FastyBird\\Core\\Exceptions\\Authentication' => 'FastyBird\\Core\\Security\\Exceptions\\Authentication',
		'FastyBird\\Core\\Exceptions\\ForbiddenAccess' => 'FastyBird\\Core\\Security\\Exceptions\\ForbiddenAccess',
		'FastyBird\\Core\\Exceptions\\UnauthorizedAccess' => 'FastyBird\\Core\\Security\\Exceptions\\UnauthorizedAccess',
		'FastyBird\\Core\\Latte\\SimpleAuth\\AccessExtension' => 'FastyBird\\Core\\Security\\Latte\\AccessExtension',
		'FastyBird\\Core\\Latte\\SimpleAuth\\Nodes\\AllowedHrefNode' => 'FastyBird\\Core\\Security\\Latte\\Nodes\\AllowedHrefNode',
		'FastyBird\\Core\\Latte\\SimpleAuth\\Nodes\\IfAllowedNode' => 'FastyBird\\Core\\Security\\Latte\\Nodes\\IfAllowedNode',
		'FastyBird\\Core\\Latte\\SimpleAuth\\Nodes\\NElseAllowedNode' => 'FastyBird\\Core\\Security\\Latte\\Nodes\\NElseAllowedNode',
		'FastyBird\\Core\\Mapping\\SimpleAuth\\Attribute\\Owner' => 'FastyBird\\Core\\Security\\Mapping\\Attribute\\Owner',
		'FastyBird\\Core\\Mapping\\SimpleAuth\\Driver\\Owner' => 'FastyBird\\Core\\Security\\Mapping\\Driver\\Owner',
		'FastyBird\\Core\\Middleware\\SimpleAuth\\Authorization' => 'FastyBird\\Core\\Security\\Middleware\\Authorization',
		'FastyBird\\Core\\Middleware\\SimpleAuth\\User' => 'FastyBird\\Core\\Security\\Middleware\\User',
		'FastyBird\\Core\\Persistence\\SimpleAuth\\Models\\Casbin\\Adapter' => 'FastyBird\\Core\\Security\\Models\\Casbin\\Adapter',
		'FastyBird\\Core\\Persistence\\SimpleAuth\\Models\\Casbin\\Filter' => 'FastyBird\\Core\\Security\\Models\\Casbin\\Filter',
		'FastyBird\\Core\\Persistence\\SimpleAuth\\Models\\Policies\\Manager' => 'FastyBird\\Core\\Security\\Models\\Policies\\Manager',
		'FastyBird\\Core\\Persistence\\SimpleAuth\\Models\\Policies\\Repository' => 'FastyBird\\Core\\Security\\Models\\Policies\\Repository',
		'FastyBird\\Core\\Persistence\\SimpleAuth\\Models\\Tokens\\Manager' => 'FastyBird\\Core\\Security\\Models\\Tokens\\Manager',
		'FastyBird\\Core\\Persistence\\SimpleAuth\\Models\\Tokens\\Repository' => 'FastyBird\\Core\\Security\\Models\\Tokens\\Repository',
		'FastyBird\\Core\\Persistence\\SimpleAuth\\Queries\\FindPolicies' => 'FastyBird\\Core\\Security\\Queries\\FindPolicies',
		'FastyBird\\Core\\Persistence\\SimpleAuth\\Queries\\FindTokens' => 'FastyBird\\Core\\Security\\Queries\\FindTokens',
		'FastyBird\\Core\\Presenters\\SimpleAuth\\TSimpleAuth' => 'FastyBird\\Core\\Security\\Presenters\\HasAuthorization',
		'FastyBird\\Core\\Security\\SimpleAuth\\Access\\AnnotationChecker' => 'FastyBird\\Core\\Security\\Access\\AnnotationChecker',
		'FastyBird\\Core\\Security\\SimpleAuth\\Access\\CheckRequirements' => 'FastyBird\\Core\\Security\\Access\\CheckRequirements',
		'FastyBird\\Core\\Security\\SimpleAuth\\Access\\Checker' => 'FastyBird\\Core\\Security\\Access\\Checker',
		'FastyBird\\Core\\Security\\SimpleAuth\\Access\\LatteChecker' => 'FastyBird\\Core\\Security\\Access\\LatteChecker',
		'FastyBird\\Core\\Security\\SimpleAuth\\Access\\LinkChecker' => 'FastyBird\\Core\\Security\\Access\\LinkChecker',
		'FastyBird\\Core\\Security\\SimpleAuth\\EnforcerFactory' => 'FastyBird\\Core\\Security\\Identity\\EnforcerFactory',
		'FastyBird\\Core\\Security\\SimpleAuth\\IAuthenticator' => 'FastyBird\\Core\\Security\\Identity\\Authenticator',
		'FastyBird\\Core\\Security\\SimpleAuth\\IIdentity' => 'FastyBird\\Core\\Security\\Identity\\UserIdentity',
		'FastyBird\\Core\\Security\\SimpleAuth\\IIdentityFactory' => 'FastyBird\\Core\\Security\\Identity\\IdentityProvider',
		'FastyBird\\Core\\Security\\SimpleAuth\\IUserStorage' => 'FastyBird\\Core\\Security\\Identity\\IUserStorage',
		'FastyBird\\Core\\Security\\SimpleAuth\\IdentityFactory' => 'FastyBird\\Core\\Security\\Identity\\IdentityFactory',
		'FastyBird\\Core\\Security\\SimpleAuth\\PlainIdentity' => 'FastyBird\\Core\\Security\\Identity\\PlainIdentity',
		'FastyBird\\Core\\Security\\SimpleAuth\\TokenBuilder' => 'FastyBird\\Core\\Security\\Identity\\TokenBuilder',
		'FastyBird\\Core\\Security\\SimpleAuth\\TokenReader' => 'FastyBird\\Core\\Security\\Identity\\TokenReader',
		'FastyBird\\Core\\Security\\SimpleAuth\\TokenValidator' => 'FastyBird\\Core\\Security\\Identity\\TokenValidator',
		'FastyBird\\Core\\Security\\SimpleAuth\\User' => 'FastyBird\\Core\\Security\\Identity\\User',
		'FastyBird\\Core\\Security\\SimpleAuth\\UserStorage' => 'FastyBird\\Core\\Security\\Identity\\UserStorage',
		'FastyBird\\Core\\Services\\SimpleAuth\\Auth' => 'FastyBird\\Core\\Security\\Services\\Auth',
		'FastyBird\\Core\\Subscribers\\SimpleAuth\\Application' => 'FastyBird\\Core\\Security\\Subscribers\\Application',
		'FastyBird\\Core\\Subscribers\\SimpleAuth\\Policy' => 'FastyBird\\Core\\Security\\Subscribers\\Policy',
		'FastyBird\\Core\\Subscribers\\SimpleAuth\\User' => 'FastyBird\\Core\\Security\\Subscribers\\User',
		'FastyBird\\Core\\Types\\SimpleAuth\\PolicyType' => 'FastyBird\\Core\\Security\\Types\\PolicyType',
		'FastyBird\\Core\\Types\\SimpleAuth\\TokenState' => 'FastyBird\\Core\\Security\\Types\\TokenState',
	],
	'normalize' => [],
	'namespaces' => [],
	'files' => [],
];
