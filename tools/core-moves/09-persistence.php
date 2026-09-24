<?php declare(strict_types = 1);

/**
 * E3.9 (#502): Persistence.
 *
 * Written by hand from the approved census, section "Persistence (45 files)" of
 * docs/superpowers/plans/2026-09-23-core-e3-capability-census.md, plus the "Name table --
 * the 27 policy-B renames" rows for the 4 DI-generated factories and the 5 external-
 * extension-point/trait-in-use types, and the "Stutter and banned-segment renames" row for
 * `Helpers\DoctrineCrud\Helpers`. 45 files, not the Epic's draft 44: the census's own note
 * explains the +1 as `Subscribers\DoctrineMigrations\SchemaSubscriber.php`, added on `main`
 * by #511 after the Epic was drafted, allocated to `Persistence\Subscribers\SchemaSubscriber`
 * beside its `TimestampableSubscriber` sibling.
 *
 * `FastyBird\Core\Persistence\` already exists as a namespace (`Application\Rules`,
 * `DoctrineCrud`, `DoctrineOrmQuery`, plus `JsonApi` and `SimpleAuth`, neither of which is
 * part of this map -- they belong to the Api and Security capabilities, #503/#506). This
 * map collapses three of its sub-namespaces into the capability's own layout:
 * `Persistence\Application\Rules\*` loses the `Application` segment (`Persistence\Rules\*`),
 * `Persistence\DoctrineCrud\Crud\*` loses `DoctrineCrud` (`Persistence\Crud\*`), and
 * `Persistence\DoctrineOrmQuery\*` becomes `Persistence\Query\*`. All three source
 * sub-namespaces fully vacate (nothing else lives under them), so no `normalize` entry is
 * needed for them -- every consumer's import resolves through the `classes` map already.
 *
 * The other 34 files relocate out of shared/root namespaces that dissolve for them:
 * `Entities\{Application\Mapping,DoctrineCrud,DoctrineTimestampable}`,
 * `Mapping\{DoctrineCrud,DoctrineTimestampable}`, `Providers\DoctrineTimestampable`,
 * `Subscribers\{DoctrineTimestampable,DoctrineMigrations}`, one file out of the still-mixed
 * `Subscribers\Application\` (its `EventLoopLifeCycle.php` sibling stays -- EventLoop is a
 * later PR, so that namespace does not vacate), `Types\DoctrineTimestampable`,
 * `Utilities\Tools` (fully vacates), `Helpers\{DoctrineCrud,Tools}` (`Helpers\Tools` fully
 * vacates -- Logger/Sentry already left in #524's move; `Helpers\JsonApi`/`Helpers\WsServer`
 * are untouched siblings, not part of this map), 2 `Events\` and 5 `Exceptions\` files out of
 * their shared roots.
 *
 * 9 interfaces/traits renamed (policy B, per the name table): `IEntity` -> `CrudEntity`
 * (external extension point), `IEntityCreated` -> `EntityCreated`, `IEntityUpdated` ->
 * `EntityUpdated` (both external extension points), `TEntityCreated` -> `HasEntityCreated`,
 * `TEntityUpdated` -> `HasEntityUpdated` (both traits in use outside Core), and the 4
 * DI-generated factories (`CoreExtension.php` `setImplement()`): `IEntityCreator` ->
 * `EntityCreatorFactory`, `IEntityDeleter` -> `EntityDeleterFactory`, `IEntityCrudFactory` ->
 * `CrudFactory`, `IEntityUpdater` -> `EntityUpdaterFactory` (each would otherwise collide
 * with its concrete `Entity*` sibling on a bare I-drop, collision table). `IEntityRemoved`,
 * `TEntityRemoved` (unreferenced anywhere), `IEntityMapper` and `IEntityCrud` are hand-off
 * types (policy B): they move but keep their `I`/`T` prefix, per the census's non-binding
 * appendix. One class renamed to resolve a stutter: `Helpers\DoctrineCrud\Helpers` (a
 * `Helpers\Helpers` stutter) -> `Persistence\Helpers\ConstructorAutowiring`, its actual role
 * (reflection-based constructor-argument autowiring for entity creation).
 *
 * Applied by tools/move-core-symbols.php; see that file for the format.
 */
return [
	'classes' => [
		'FastyBird\\Core\\Entities\\Application\\Mapping\\DiscriminatorEntry' => 'FastyBird\\Core\\Persistence\\Mapping\\DiscriminatorEntry',
		'FastyBird\\Core\\Entities\\DoctrineCrud\\IEntity' => 'FastyBird\\Core\\Persistence\\Entities\\CrudEntity',
		'FastyBird\\Core\\Entities\\DoctrineTimestampable\\IEntityCreated' => 'FastyBird\\Core\\Persistence\\Entities\\EntityCreated',
		'FastyBird\\Core\\Entities\\DoctrineTimestampable\\IEntityRemoved' => 'FastyBird\\Core\\Persistence\\Entities\\IEntityRemoved',
		'FastyBird\\Core\\Entities\\DoctrineTimestampable\\IEntityUpdated' => 'FastyBird\\Core\\Persistence\\Entities\\EntityUpdated',
		'FastyBird\\Core\\Entities\\DoctrineTimestampable\\TEntityCreated' => 'FastyBird\\Core\\Persistence\\Entities\\HasEntityCreated',
		'FastyBird\\Core\\Entities\\DoctrineTimestampable\\TEntityRemoved' => 'FastyBird\\Core\\Persistence\\Entities\\TEntityRemoved',
		'FastyBird\\Core\\Entities\\DoctrineTimestampable\\TEntityUpdated' => 'FastyBird\\Core\\Persistence\\Entities\\HasEntityUpdated',
		'FastyBird\\Core\\Events\\DbTransactionFinished' => 'FastyBird\\Core\\Persistence\\Events\\DbTransactionFinished',
		'FastyBird\\Core\\Events\\DbTransactionStarted' => 'FastyBird\\Core\\Persistence\\Events\\DbTransactionStarted',
		'FastyBird\\Core\\Exceptions\\EntityCreation' => 'FastyBird\\Core\\Persistence\\Exceptions\\EntityCreation',
		'FastyBird\\Core\\Exceptions\\InvalidMapping' => 'FastyBird\\Core\\Persistence\\Exceptions\\InvalidMapping',
		'FastyBird\\Core\\Exceptions\\MissingRequiredField' => 'FastyBird\\Core\\Persistence\\Exceptions\\MissingRequiredField',
		'FastyBird\\Core\\Exceptions\\Query' => 'FastyBird\\Core\\Persistence\\Exceptions\\Query',
		'FastyBird\\Core\\Exceptions\\QueryNotImplemented' => 'FastyBird\\Core\\Persistence\\Exceptions\\QueryNotImplemented',
		'FastyBird\\Core\\Helpers\\DoctrineCrud\\Helpers' => 'FastyBird\\Core\\Persistence\\Helpers\\ConstructorAutowiring',
		'FastyBird\\Core\\Helpers\\DoctrineCrud\\StringFunctions\\DateFormat' => 'FastyBird\\Core\\Persistence\\Helpers\\StringFunctions\\DateFormat',
		'FastyBird\\Core\\Helpers\\Tools\\Database' => 'FastyBird\\Core\\Persistence\\Helpers\\Database',
		'FastyBird\\Core\\Mapping\\DoctrineCrud\\Attribute\\Crud' => 'FastyBird\\Core\\Persistence\\Mapping\\Attribute\\Crud',
		'FastyBird\\Core\\Mapping\\DoctrineCrud\\EntityMapper' => 'FastyBird\\Core\\Persistence\\Mapping\\EntityMapper',
		'FastyBird\\Core\\Mapping\\DoctrineCrud\\IEntityMapper' => 'FastyBird\\Core\\Persistence\\Mapping\\IEntityMapper',
		'FastyBird\\Core\\Mapping\\DoctrineTimestampable\\Annotation\\Timestampable' => 'FastyBird\\Core\\Persistence\\Mapping\\Annotation\\Timestampable',
		'FastyBird\\Core\\Mapping\\DoctrineTimestampable\\Driver\\Timestampable' => 'FastyBird\\Core\\Persistence\\Mapping\\Driver\\Timestampable',
		'FastyBird\\Core\\Persistence\\Application\\Rules\\UuidArgs' => 'FastyBird\\Core\\Persistence\\Rules\\UuidArgs',
		'FastyBird\\Core\\Persistence\\Application\\Rules\\UuidRule' => 'FastyBird\\Core\\Persistence\\Rules\\UuidRule',
		'FastyBird\\Core\\Persistence\\Application\\Rules\\UuidValue' => 'FastyBird\\Core\\Persistence\\Rules\\UuidValue',
		'FastyBird\\Core\\Persistence\\DoctrineCrud\\Crud\\Create\\EntityCreator' => 'FastyBird\\Core\\Persistence\\Crud\\Create\\EntityCreator',
		'FastyBird\\Core\\Persistence\\DoctrineCrud\\Crud\\Create\\IEntityCreator' => 'FastyBird\\Core\\Persistence\\Crud\\Create\\EntityCreatorFactory',
		'FastyBird\\Core\\Persistence\\DoctrineCrud\\Crud\\CrudManager' => 'FastyBird\\Core\\Persistence\\Crud\\CrudManager',
		'FastyBird\\Core\\Persistence\\DoctrineCrud\\Crud\\Delete\\EntityDeleter' => 'FastyBird\\Core\\Persistence\\Crud\\Delete\\EntityDeleter',
		'FastyBird\\Core\\Persistence\\DoctrineCrud\\Crud\\Delete\\IEntityDeleter' => 'FastyBird\\Core\\Persistence\\Crud\\Delete\\EntityDeleterFactory',
		'FastyBird\\Core\\Persistence\\DoctrineCrud\\Crud\\EntityCrud' => 'FastyBird\\Core\\Persistence\\Crud\\EntityCrud',
		'FastyBird\\Core\\Persistence\\DoctrineCrud\\Crud\\EntityCrudFactory' => 'FastyBird\\Core\\Persistence\\Crud\\EntityCrudFactory',
		'FastyBird\\Core\\Persistence\\DoctrineCrud\\Crud\\IEntityCrud' => 'FastyBird\\Core\\Persistence\\Crud\\IEntityCrud',
		'FastyBird\\Core\\Persistence\\DoctrineCrud\\Crud\\IEntityCrudFactory' => 'FastyBird\\Core\\Persistence\\Crud\\CrudFactory',
		'FastyBird\\Core\\Persistence\\DoctrineCrud\\Crud\\Update\\EntityUpdater' => 'FastyBird\\Core\\Persistence\\Crud\\Update\\EntityUpdater',
		'FastyBird\\Core\\Persistence\\DoctrineCrud\\Crud\\Update\\IEntityUpdater' => 'FastyBird\\Core\\Persistence\\Crud\\Update\\EntityUpdaterFactory',
		'FastyBird\\Core\\Persistence\\DoctrineOrmQuery\\QueryObject' => 'FastyBird\\Core\\Persistence\\Query\\QueryObject',
		'FastyBird\\Core\\Persistence\\DoctrineOrmQuery\\ResultSet' => 'FastyBird\\Core\\Persistence\\Query\\ResultSet',
		'FastyBird\\Core\\Providers\\DoctrineTimestampable\\DateProvider' => 'FastyBird\\Core\\Persistence\\Providers\\DateProvider',
		'FastyBird\\Core\\Subscribers\\Application\\EntityDiscriminator' => 'FastyBird\\Core\\Persistence\\Subscribers\\EntityDiscriminator',
		'FastyBird\\Core\\Subscribers\\DoctrineMigrations\\SchemaSubscriber' => 'FastyBird\\Core\\Persistence\\Subscribers\\SchemaSubscriber',
		'FastyBird\\Core\\Subscribers\\DoctrineTimestampable\\TimestampableSubscriber' => 'FastyBird\\Core\\Persistence\\Subscribers\\TimestampableSubscriber',
		'FastyBird\\Core\\Types\\DoctrineTimestampable\\UTCDateTime' => 'FastyBird\\Core\\Persistence\\Types\\UTCDateTime',
		'FastyBird\\Core\\Utilities\\Tools\\DateTimeProvider' => 'FastyBird\\Core\\Persistence\\Utilities\\DateTimeProvider',
	],
	'normalize' => [],
	'namespaces' => [],
	'files' => [],
];
