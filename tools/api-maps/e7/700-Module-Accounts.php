<?php declare(strict_types = 1);

/**
 * #700 (E7.6, #462): the API change list of the Accounts module. The format is documented in
 * tools/api-surface.php; the base is the manifest of `main` @ 746772d5e (--write from a checkout of it).
 *
 * - renamed: the I-interfaces and T-traits, to their census T1 role names;
 * - changed `final` / `readonly`: census T4 and T5;
 * - removed __call(), __callStatic(), __get(), __set(), __isset(), __unset() and changed `traits`:
 *   Nette\SmartObject is no longer used (census T6);
 * - Accounts only: Helpers\Password's getSalt()/setSalt() become the `public private(set)` $salt
 *   (census T3);
 * - the locators (census T8): Router\Validator takes the router instead of the DI container, Ui's
 *   Hydrators\Widgets\Widget the schema container and the data source hydrators; the
 *   `@throws` only the lookups raised go with them.
 */

return [
	'renamed' => [
		'FastyBird\\Module\\Accounts\\Controllers\\Finders\\TAccount' => 'FastyBird\\Module\\Accounts\\Controllers\\Finders\\FindsAccount',
		'FastyBird\\Module\\Accounts\\Controllers\\Finders\\TEmail' => 'FastyBird\\Module\\Accounts\\Controllers\\Finders\\FindsEmail',
		'FastyBird\\Module\\Accounts\\Controllers\\Finders\\TIdentity' => 'FastyBird\\Module\\Accounts\\Controllers\\Finders\\FindsIdentity',
		'FastyBird\\Module\\Accounts\\Controllers\\Finders\\TRole' => 'FastyBird\\Module\\Accounts\\Controllers\\Finders\\FindsRole',
		'FastyBird\\Module\\Accounts\\Entities\\TEntity' => 'FastyBird\\Module\\Accounts\\Entities\\HasEntityId',
		'FastyBird\\Module\\Accounts\\Entities\\TEntityParams' => 'FastyBird\\Module\\Accounts\\Entities\\HasEntityParams',
		'FastyBird\\Module\\Accounts\\Hydrators\\Accounts\\TAccount' => 'FastyBird\\Module\\Accounts\\Hydrators\\Accounts\\HydratesAccount',
		'FastyBird\\Module\\Accounts\\Hydrators\\Emails\\TEmail' => 'FastyBird\\Module\\Accounts\\Hydrators\\Emails\\HydratesEmail',
	],
	'removed' => [
		'FastyBird\\Module\\Accounts\\Controllers\\BaseV1::__call()',
		'FastyBird\\Module\\Accounts\\Controllers\\BaseV1::__callStatic()',
		'FastyBird\\Module\\Accounts\\Controllers\\BaseV1::__get()',
		'FastyBird\\Module\\Accounts\\Controllers\\BaseV1::__isset()',
		'FastyBird\\Module\\Accounts\\Controllers\\BaseV1::__set()',
		'FastyBird\\Module\\Accounts\\Controllers\\BaseV1::__unset()',
		'FastyBird\\Module\\Accounts\\Helpers\\Password::__call()',
		'FastyBird\\Module\\Accounts\\Helpers\\Password::__callStatic()',
		'FastyBird\\Module\\Accounts\\Helpers\\Password::__get()',
		'FastyBird\\Module\\Accounts\\Helpers\\Password::__isset()',
		'FastyBird\\Module\\Accounts\\Helpers\\Password::__set()',
		'FastyBird\\Module\\Accounts\\Helpers\\Password::__unset()',
		'FastyBird\\Module\\Accounts\\Helpers\\Password::getSalt()',
		'FastyBird\\Module\\Accounts\\Helpers\\Password::setSalt()',
		'FastyBird\\Module\\Accounts\\Helpers\\SecurityHash::__call()',
		'FastyBird\\Module\\Accounts\\Helpers\\SecurityHash::__callStatic()',
		'FastyBird\\Module\\Accounts\\Helpers\\SecurityHash::__get()',
		'FastyBird\\Module\\Accounts\\Helpers\\SecurityHash::__isset()',
		'FastyBird\\Module\\Accounts\\Helpers\\SecurityHash::__set()',
		'FastyBird\\Module\\Accounts\\Helpers\\SecurityHash::__unset()',
		'FastyBird\\Module\\Accounts\\Models\\Entities\\Accounts\\AccountsManager::__call()',
		'FastyBird\\Module\\Accounts\\Models\\Entities\\Accounts\\AccountsManager::__callStatic()',
		'FastyBird\\Module\\Accounts\\Models\\Entities\\Accounts\\AccountsManager::__get()',
		'FastyBird\\Module\\Accounts\\Models\\Entities\\Accounts\\AccountsManager::__isset()',
		'FastyBird\\Module\\Accounts\\Models\\Entities\\Accounts\\AccountsManager::__set()',
		'FastyBird\\Module\\Accounts\\Models\\Entities\\Accounts\\AccountsManager::__unset()',
		'FastyBird\\Module\\Accounts\\Models\\Entities\\Accounts\\AccountsRepository::__call()',
		'FastyBird\\Module\\Accounts\\Models\\Entities\\Accounts\\AccountsRepository::__callStatic()',
		'FastyBird\\Module\\Accounts\\Models\\Entities\\Accounts\\AccountsRepository::__get()',
		'FastyBird\\Module\\Accounts\\Models\\Entities\\Accounts\\AccountsRepository::__isset()',
		'FastyBird\\Module\\Accounts\\Models\\Entities\\Accounts\\AccountsRepository::__set()',
		'FastyBird\\Module\\Accounts\\Models\\Entities\\Accounts\\AccountsRepository::__unset()',
		'FastyBird\\Module\\Accounts\\Models\\Entities\\Emails\\EmailsManager::__call()',
		'FastyBird\\Module\\Accounts\\Models\\Entities\\Emails\\EmailsManager::__callStatic()',
		'FastyBird\\Module\\Accounts\\Models\\Entities\\Emails\\EmailsManager::__get()',
		'FastyBird\\Module\\Accounts\\Models\\Entities\\Emails\\EmailsManager::__isset()',
		'FastyBird\\Module\\Accounts\\Models\\Entities\\Emails\\EmailsManager::__set()',
		'FastyBird\\Module\\Accounts\\Models\\Entities\\Emails\\EmailsManager::__unset()',
		'FastyBird\\Module\\Accounts\\Models\\Entities\\Emails\\EmailsRepository::__call()',
		'FastyBird\\Module\\Accounts\\Models\\Entities\\Emails\\EmailsRepository::__callStatic()',
		'FastyBird\\Module\\Accounts\\Models\\Entities\\Emails\\EmailsRepository::__get()',
		'FastyBird\\Module\\Accounts\\Models\\Entities\\Emails\\EmailsRepository::__isset()',
		'FastyBird\\Module\\Accounts\\Models\\Entities\\Emails\\EmailsRepository::__set()',
		'FastyBird\\Module\\Accounts\\Models\\Entities\\Emails\\EmailsRepository::__unset()',
		'FastyBird\\Module\\Accounts\\Models\\Entities\\Identities\\IdentitiesManager::__call()',
		'FastyBird\\Module\\Accounts\\Models\\Entities\\Identities\\IdentitiesManager::__callStatic()',
		'FastyBird\\Module\\Accounts\\Models\\Entities\\Identities\\IdentitiesManager::__get()',
		'FastyBird\\Module\\Accounts\\Models\\Entities\\Identities\\IdentitiesManager::__isset()',
		'FastyBird\\Module\\Accounts\\Models\\Entities\\Identities\\IdentitiesManager::__set()',
		'FastyBird\\Module\\Accounts\\Models\\Entities\\Identities\\IdentitiesManager::__unset()',
		'FastyBird\\Module\\Accounts\\Models\\Entities\\Identities\\IdentitiesRepository::__call()',
		'FastyBird\\Module\\Accounts\\Models\\Entities\\Identities\\IdentitiesRepository::__callStatic()',
		'FastyBird\\Module\\Accounts\\Models\\Entities\\Identities\\IdentitiesRepository::__get()',
		'FastyBird\\Module\\Accounts\\Models\\Entities\\Identities\\IdentitiesRepository::__isset()',
		'FastyBird\\Module\\Accounts\\Models\\Entities\\Identities\\IdentitiesRepository::__set()',
		'FastyBird\\Module\\Accounts\\Models\\Entities\\Identities\\IdentitiesRepository::__unset()',
		'FastyBird\\Module\\Accounts\\Subscribers\\AccountEntity::__call()',
		'FastyBird\\Module\\Accounts\\Subscribers\\AccountEntity::__callStatic()',
		'FastyBird\\Module\\Accounts\\Subscribers\\AccountEntity::__get()',
		'FastyBird\\Module\\Accounts\\Subscribers\\AccountEntity::__isset()',
		'FastyBird\\Module\\Accounts\\Subscribers\\AccountEntity::__set()',
		'FastyBird\\Module\\Accounts\\Subscribers\\AccountEntity::__unset()',
		'FastyBird\\Module\\Accounts\\Subscribers\\EmailEntity::__call()',
		'FastyBird\\Module\\Accounts\\Subscribers\\EmailEntity::__callStatic()',
		'FastyBird\\Module\\Accounts\\Subscribers\\EmailEntity::__get()',
		'FastyBird\\Module\\Accounts\\Subscribers\\EmailEntity::__isset()',
		'FastyBird\\Module\\Accounts\\Subscribers\\EmailEntity::__set()',
		'FastyBird\\Module\\Accounts\\Subscribers\\EmailEntity::__unset()',
		'FastyBird\\Module\\Accounts\\Subscribers\\ModuleEntities::__call()',
		'FastyBird\\Module\\Accounts\\Subscribers\\ModuleEntities::__callStatic()',
		'FastyBird\\Module\\Accounts\\Subscribers\\ModuleEntities::__get()',
		'FastyBird\\Module\\Accounts\\Subscribers\\ModuleEntities::__isset()',
		'FastyBird\\Module\\Accounts\\Subscribers\\ModuleEntities::__set()',
		'FastyBird\\Module\\Accounts\\Subscribers\\ModuleEntities::__unset()',
	],
	'added' => [
		'FastyBird\\Module\\Accounts\\Helpers\\Password::$salt' => [
			'visibility' => 'public',
			'set' => 'private',
			'static' => false,
			'readonly' => false,
			'type' => 'string',
			'hasDefault' => false,
			'default' => null,
			'promoted' => false,
			'virtual' => false,
			'final' => true,
			'abstract' => false,
			'hooks' => [],
			'attributes' => [],
		],
	],
	'changed' => [
		'FastyBird\\Module\\Accounts\\Commands\\Accounts\\Create' => [
			'final' => true,
		],
		'FastyBird\\Module\\Accounts\\Commands\\Install' => [
			'final' => true,
		],
		'FastyBird\\Module\\Accounts\\Controllers\\BaseV1' => [
			'traits' => [],
		],
		'FastyBird\\Module\\Accounts\\DI\\AccountsExtension' => [
			'final' => true,
		],
		'FastyBird\\Module\\Accounts\\Exceptions\\AccountNotFound' => [
			'final' => true,
		],
		'FastyBird\\Module\\Accounts\\Exceptions\\AccountRoleInvalid' => [
			'final' => true,
		],
		'FastyBird\\Module\\Accounts\\Exceptions\\AuthenticationFailed' => [
			'final' => true,
		],
		'FastyBird\\Module\\Accounts\\Exceptions\\EmailAlreadyTaken' => [
			'final' => true,
		],
		'FastyBird\\Module\\Accounts\\Exceptions\\EmailHaveToBeDefault' => [
			'final' => true,
		],
		'FastyBird\\Module\\Accounts\\Exceptions\\EmailIsNotValid' => [
			'final' => true,
		],
		'FastyBird\\Module\\Accounts\\Exceptions\\InvalidState' => [
			'final' => true,
		],
		'FastyBird\\Module\\Accounts\\Exceptions\\PropertyNotExists' => [
			'final' => true,
		],
		'FastyBird\\Module\\Accounts\\Exceptions\\Runtime' => [
			'final' => true,
		],
		'FastyBird\\Module\\Accounts\\Helpers\\Password' => [
			'traits' => [],
		],
		'FastyBird\\Module\\Accounts\\Helpers\\SecurityHash' => [
			'readonly' => true,
			'traits' => [],
		],
		'FastyBird\\Module\\Accounts\\Hydrators\\Identities\\Identity' => [
			'final' => true,
		],
		'FastyBird\\Module\\Accounts\\Models\\Entities\\Accounts\\AccountsManager' => [
			'traits' => [],
		],
		'FastyBird\\Module\\Accounts\\Models\\Entities\\Accounts\\AccountsRepository' => [
			'traits' => [],
		],
		'FastyBird\\Module\\Accounts\\Models\\Entities\\Emails\\EmailsManager' => [
			'traits' => [],
		],
		'FastyBird\\Module\\Accounts\\Models\\Entities\\Emails\\EmailsRepository' => [
			'traits' => [],
		],
		'FastyBird\\Module\\Accounts\\Models\\Entities\\Identities\\IdentitiesManager' => [
			'final' => true,
			'traits' => [],
		],
		'FastyBird\\Module\\Accounts\\Models\\Entities\\Identities\\IdentitiesRepository' => [
			'traits' => [],
		],
		'FastyBird\\Module\\Accounts\\Presenters\\AccountPresenter' => [
			'final' => true,
		],
		'FastyBird\\Module\\Accounts\\Presenters\\SignPresenter' => [
			'final' => true,
		],
		'FastyBird\\Module\\Accounts\\Queries\\Entities\\FindAccounts' => [
			'final' => true,
		],
		'FastyBird\\Module\\Accounts\\Queries\\Entities\\FindEmails' => [
			'final' => true,
		],
		'FastyBird\\Module\\Accounts\\Queries\\Entities\\FindIdentities' => [
			'final' => true,
		],
		'FastyBird\\Module\\Accounts\\Queries\\Entities\\FindRoles' => [
			'final' => true,
		],
		'FastyBird\\Module\\Accounts\\Router\\ApiRoutes' => [
			'final' => true,
			'readonly' => true,
		],
		'FastyBird\\Module\\Accounts\\Router\\Validator::__construct()' => [
			'-parameters.$container' => null,
			'parameters.$router' => [
				'position' => 0,
				'type' => 'FastyBird\\Core\\Http\\Routing\\Router',
				'optional' => false,
				'default' => null,
				'defaultConstant' => null,
				'variadic' => false,
				'byRef' => false,
				'promoted' => true,
				'attributes' => [],
			],
		],
		'FastyBird\\Module\\Accounts\\Router\\Validator::validate()' => [
			'throws' => [],
		],
		'FastyBird\\Module\\Accounts\\Router\\Validator' => [
			'final' => true,
		],
		'FastyBird\\Module\\Accounts\\Security\\Authenticator' => [
			'readonly' => true,
		],
		'FastyBird\\Module\\Accounts\\Security\\IdentityFactory' => [
			'final' => true,
		],
		'FastyBird\\Module\\Accounts\\Security\\User' => [
			'final' => true,
		],
		'FastyBird\\Module\\Accounts\\Subscribers\\AccountEntity' => [
			'readonly' => true,
			'traits' => [],
		],
		'FastyBird\\Module\\Accounts\\Subscribers\\EmailEntity' => [
			'readonly' => true,
			'traits' => [],
		],
		'FastyBird\\Module\\Accounts\\Subscribers\\ModuleEntities' => [
			'readonly' => true,
			'traits' => [],
		],
		'FastyBird\\Module\\Accounts\\Utilities\\Api' => [
			'final' => true,
		],
		'FastyBird\\Module\\Accounts\\Utilities\\UserPanel' => [
			'final' => true,
			'readonly' => true,
		],
	],
];
