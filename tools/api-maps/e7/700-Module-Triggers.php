<?php declare(strict_types = 1);

/**
 * #700 (E7.6, #462): the API change list of the Triggers module. The format is documented in
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
		'FastyBird\\Module\\Triggers\\Controllers\\Finders\\TTrigger' => 'FastyBird\\Module\\Triggers\\Controllers\\Finders\\FindsTrigger',
		'FastyBird\\Module\\Triggers\\Entities\\TEntity' => 'FastyBird\\Module\\Triggers\\Entities\\HasEntityId',
		'FastyBird\\Module\\Triggers\\Entities\\TEntityParams' => 'FastyBird\\Module\\Triggers\\Entities\\HasEntityParams',
		'FastyBird\\Module\\Triggers\\Models\\States\\IActionsManager' => 'FastyBird\\Module\\Triggers\\Models\\States\\ActionsWriter',
		'FastyBird\\Module\\Triggers\\Models\\States\\IActionsRepository' => 'FastyBird\\Module\\Triggers\\Models\\States\\ActionsReader',
		'FastyBird\\Module\\Triggers\\Models\\States\\IConditionsManager' => 'FastyBird\\Module\\Triggers\\Models\\States\\ConditionsWriter',
		'FastyBird\\Module\\Triggers\\Models\\States\\IConditionsRepository' => 'FastyBird\\Module\\Triggers\\Models\\States\\ConditionsReader',
	],
	'removed' => [
		'FastyBird\\Module\\Triggers\\Controllers\\BaseV1::__call()',
		'FastyBird\\Module\\Triggers\\Controllers\\BaseV1::__callStatic()',
		'FastyBird\\Module\\Triggers\\Controllers\\BaseV1::__get()',
		'FastyBird\\Module\\Triggers\\Controllers\\BaseV1::__isset()',
		'FastyBird\\Module\\Triggers\\Controllers\\BaseV1::__set()',
		'FastyBird\\Module\\Triggers\\Controllers\\BaseV1::__unset()',
		'FastyBird\\Module\\Triggers\\Models\\Entities\\Actions\\ActionsManager::__call()',
		'FastyBird\\Module\\Triggers\\Models\\Entities\\Actions\\ActionsManager::__callStatic()',
		'FastyBird\\Module\\Triggers\\Models\\Entities\\Actions\\ActionsManager::__get()',
		'FastyBird\\Module\\Triggers\\Models\\Entities\\Actions\\ActionsManager::__isset()',
		'FastyBird\\Module\\Triggers\\Models\\Entities\\Actions\\ActionsManager::__set()',
		'FastyBird\\Module\\Triggers\\Models\\Entities\\Actions\\ActionsManager::__unset()',
		'FastyBird\\Module\\Triggers\\Models\\Entities\\Actions\\ActionsRepository::__call()',
		'FastyBird\\Module\\Triggers\\Models\\Entities\\Actions\\ActionsRepository::__callStatic()',
		'FastyBird\\Module\\Triggers\\Models\\Entities\\Actions\\ActionsRepository::__get()',
		'FastyBird\\Module\\Triggers\\Models\\Entities\\Actions\\ActionsRepository::__isset()',
		'FastyBird\\Module\\Triggers\\Models\\Entities\\Actions\\ActionsRepository::__set()',
		'FastyBird\\Module\\Triggers\\Models\\Entities\\Actions\\ActionsRepository::__unset()',
		'FastyBird\\Module\\Triggers\\Models\\Entities\\Conditions\\ConditionsManager::__call()',
		'FastyBird\\Module\\Triggers\\Models\\Entities\\Conditions\\ConditionsManager::__callStatic()',
		'FastyBird\\Module\\Triggers\\Models\\Entities\\Conditions\\ConditionsManager::__get()',
		'FastyBird\\Module\\Triggers\\Models\\Entities\\Conditions\\ConditionsManager::__isset()',
		'FastyBird\\Module\\Triggers\\Models\\Entities\\Conditions\\ConditionsManager::__set()',
		'FastyBird\\Module\\Triggers\\Models\\Entities\\Conditions\\ConditionsManager::__unset()',
		'FastyBird\\Module\\Triggers\\Models\\Entities\\Conditions\\ConditionsRepository::__call()',
		'FastyBird\\Module\\Triggers\\Models\\Entities\\Conditions\\ConditionsRepository::__callStatic()',
		'FastyBird\\Module\\Triggers\\Models\\Entities\\Conditions\\ConditionsRepository::__get()',
		'FastyBird\\Module\\Triggers\\Models\\Entities\\Conditions\\ConditionsRepository::__isset()',
		'FastyBird\\Module\\Triggers\\Models\\Entities\\Conditions\\ConditionsRepository::__set()',
		'FastyBird\\Module\\Triggers\\Models\\Entities\\Conditions\\ConditionsRepository::__unset()',
		'FastyBird\\Module\\Triggers\\Models\\Entities\\Notifications\\NotificationsManager::__call()',
		'FastyBird\\Module\\Triggers\\Models\\Entities\\Notifications\\NotificationsManager::__callStatic()',
		'FastyBird\\Module\\Triggers\\Models\\Entities\\Notifications\\NotificationsManager::__get()',
		'FastyBird\\Module\\Triggers\\Models\\Entities\\Notifications\\NotificationsManager::__isset()',
		'FastyBird\\Module\\Triggers\\Models\\Entities\\Notifications\\NotificationsManager::__set()',
		'FastyBird\\Module\\Triggers\\Models\\Entities\\Notifications\\NotificationsManager::__unset()',
		'FastyBird\\Module\\Triggers\\Models\\Entities\\Notifications\\NotificationsRepository::__call()',
		'FastyBird\\Module\\Triggers\\Models\\Entities\\Notifications\\NotificationsRepository::__callStatic()',
		'FastyBird\\Module\\Triggers\\Models\\Entities\\Notifications\\NotificationsRepository::__get()',
		'FastyBird\\Module\\Triggers\\Models\\Entities\\Notifications\\NotificationsRepository::__isset()',
		'FastyBird\\Module\\Triggers\\Models\\Entities\\Notifications\\NotificationsRepository::__set()',
		'FastyBird\\Module\\Triggers\\Models\\Entities\\Notifications\\NotificationsRepository::__unset()',
		'FastyBird\\Module\\Triggers\\Models\\Entities\\Triggers\\Controls\\ControlsManager::__call()',
		'FastyBird\\Module\\Triggers\\Models\\Entities\\Triggers\\Controls\\ControlsManager::__callStatic()',
		'FastyBird\\Module\\Triggers\\Models\\Entities\\Triggers\\Controls\\ControlsManager::__get()',
		'FastyBird\\Module\\Triggers\\Models\\Entities\\Triggers\\Controls\\ControlsManager::__isset()',
		'FastyBird\\Module\\Triggers\\Models\\Entities\\Triggers\\Controls\\ControlsManager::__set()',
		'FastyBird\\Module\\Triggers\\Models\\Entities\\Triggers\\Controls\\ControlsManager::__unset()',
		'FastyBird\\Module\\Triggers\\Models\\Entities\\Triggers\\Controls\\ControlsRepository::__call()',
		'FastyBird\\Module\\Triggers\\Models\\Entities\\Triggers\\Controls\\ControlsRepository::__callStatic()',
		'FastyBird\\Module\\Triggers\\Models\\Entities\\Triggers\\Controls\\ControlsRepository::__get()',
		'FastyBird\\Module\\Triggers\\Models\\Entities\\Triggers\\Controls\\ControlsRepository::__isset()',
		'FastyBird\\Module\\Triggers\\Models\\Entities\\Triggers\\Controls\\ControlsRepository::__set()',
		'FastyBird\\Module\\Triggers\\Models\\Entities\\Triggers\\Controls\\ControlsRepository::__unset()',
		'FastyBird\\Module\\Triggers\\Models\\Entities\\Triggers\\TriggersManager::__call()',
		'FastyBird\\Module\\Triggers\\Models\\Entities\\Triggers\\TriggersManager::__callStatic()',
		'FastyBird\\Module\\Triggers\\Models\\Entities\\Triggers\\TriggersManager::__get()',
		'FastyBird\\Module\\Triggers\\Models\\Entities\\Triggers\\TriggersManager::__isset()',
		'FastyBird\\Module\\Triggers\\Models\\Entities\\Triggers\\TriggersManager::__set()',
		'FastyBird\\Module\\Triggers\\Models\\Entities\\Triggers\\TriggersManager::__unset()',
		'FastyBird\\Module\\Triggers\\Models\\Entities\\Triggers\\TriggersRepository::__call()',
		'FastyBird\\Module\\Triggers\\Models\\Entities\\Triggers\\TriggersRepository::__callStatic()',
		'FastyBird\\Module\\Triggers\\Models\\Entities\\Triggers\\TriggersRepository::__get()',
		'FastyBird\\Module\\Triggers\\Models\\Entities\\Triggers\\TriggersRepository::__isset()',
		'FastyBird\\Module\\Triggers\\Models\\Entities\\Triggers\\TriggersRepository::__set()',
		'FastyBird\\Module\\Triggers\\Models\\Entities\\Triggers\\TriggersRepository::__unset()',
		'FastyBird\\Module\\Triggers\\Models\\States\\ActionsManager::__call()',
		'FastyBird\\Module\\Triggers\\Models\\States\\ActionsManager::__callStatic()',
		'FastyBird\\Module\\Triggers\\Models\\States\\ActionsManager::__get()',
		'FastyBird\\Module\\Triggers\\Models\\States\\ActionsManager::__isset()',
		'FastyBird\\Module\\Triggers\\Models\\States\\ActionsManager::__set()',
		'FastyBird\\Module\\Triggers\\Models\\States\\ActionsManager::__unset()',
		'FastyBird\\Module\\Triggers\\Models\\States\\ActionsRepository::__call()',
		'FastyBird\\Module\\Triggers\\Models\\States\\ActionsRepository::__callStatic()',
		'FastyBird\\Module\\Triggers\\Models\\States\\ActionsRepository::__get()',
		'FastyBird\\Module\\Triggers\\Models\\States\\ActionsRepository::__isset()',
		'FastyBird\\Module\\Triggers\\Models\\States\\ActionsRepository::__set()',
		'FastyBird\\Module\\Triggers\\Models\\States\\ActionsRepository::__unset()',
		'FastyBird\\Module\\Triggers\\Models\\States\\ConditionsManager::__call()',
		'FastyBird\\Module\\Triggers\\Models\\States\\ConditionsManager::__callStatic()',
		'FastyBird\\Module\\Triggers\\Models\\States\\ConditionsManager::__get()',
		'FastyBird\\Module\\Triggers\\Models\\States\\ConditionsManager::__isset()',
		'FastyBird\\Module\\Triggers\\Models\\States\\ConditionsManager::__set()',
		'FastyBird\\Module\\Triggers\\Models\\States\\ConditionsManager::__unset()',
		'FastyBird\\Module\\Triggers\\Models\\States\\ConditionsRepository::__call()',
		'FastyBird\\Module\\Triggers\\Models\\States\\ConditionsRepository::__callStatic()',
		'FastyBird\\Module\\Triggers\\Models\\States\\ConditionsRepository::__get()',
		'FastyBird\\Module\\Triggers\\Models\\States\\ConditionsRepository::__isset()',
		'FastyBird\\Module\\Triggers\\Models\\States\\ConditionsRepository::__set()',
		'FastyBird\\Module\\Triggers\\Models\\States\\ConditionsRepository::__unset()',
		'FastyBird\\Module\\Triggers\\Subscribers\\ModuleEntities::__call()',
		'FastyBird\\Module\\Triggers\\Subscribers\\ModuleEntities::__callStatic()',
		'FastyBird\\Module\\Triggers\\Subscribers\\ModuleEntities::__get()',
		'FastyBird\\Module\\Triggers\\Subscribers\\ModuleEntities::__isset()',
		'FastyBird\\Module\\Triggers\\Subscribers\\ModuleEntities::__set()',
		'FastyBird\\Module\\Triggers\\Subscribers\\ModuleEntities::__unset()',
		'FastyBird\\Module\\Triggers\\Subscribers\\NotificationEntity::__call()',
		'FastyBird\\Module\\Triggers\\Subscribers\\NotificationEntity::__callStatic()',
		'FastyBird\\Module\\Triggers\\Subscribers\\NotificationEntity::__get()',
		'FastyBird\\Module\\Triggers\\Subscribers\\NotificationEntity::__isset()',
		'FastyBird\\Module\\Triggers\\Subscribers\\NotificationEntity::__set()',
		'FastyBird\\Module\\Triggers\\Subscribers\\NotificationEntity::__unset()',
	],
	'changed' => [
		'FastyBird\\Module\\Triggers\\Commands\\Install' => [
			'final' => true,
		],
		'FastyBird\\Module\\Triggers\\Controllers\\BaseV1' => [
			'traits' => [],
		],
		'FastyBird\\Module\\Triggers\\DI\\TriggersExtension' => [
			'final' => true,
		],
		'FastyBird\\Module\\Triggers\\Documents\\Triggers\\Controls\\Control' => [
			'readonly' => true,
		],
		'FastyBird\\Module\\Triggers\\Events\\AfterAutomatorStart' => [
			'final' => true,
		],
		'FastyBird\\Module\\Triggers\\Events\\AfterAutomatorTerminate' => [
			'final' => true,
		],
		'FastyBird\\Module\\Triggers\\Events\\AutomatorStartup' => [
			'final' => true,
		],
		'FastyBird\\Module\\Triggers\\Events\\BeforeAutomatorStart' => [
			'final' => true,
		],
		'FastyBird\\Module\\Triggers\\Events\\BeforeAutomatorTerminate' => [
			'final' => true,
		],
		'FastyBird\\Module\\Triggers\\Exceptions\\InvalidArgument' => [
			'final' => true,
		],
		'FastyBird\\Module\\Triggers\\Exceptions\\NotImplemented' => [
			'final' => true,
		],
		'FastyBird\\Module\\Triggers\\Exceptions\\Runtime' => [
			'final' => true,
		],
		'FastyBird\\Module\\Triggers\\Exceptions\\UniqueNotificationEmailConstraint' => [
			'final' => true,
		],
		'FastyBird\\Module\\Triggers\\Exceptions\\UniqueNotificationNumberConstraint' => [
			'final' => true,
		],
		'FastyBird\\Module\\Triggers\\Models\\Entities\\Actions\\ActionsManager' => [
			'traits' => [],
		],
		'FastyBird\\Module\\Triggers\\Models\\Entities\\Actions\\ActionsRepository' => [
			'traits' => [],
		],
		'FastyBird\\Module\\Triggers\\Models\\Entities\\Conditions\\ConditionsManager' => [
			'traits' => [],
		],
		'FastyBird\\Module\\Triggers\\Models\\Entities\\Conditions\\ConditionsRepository' => [
			'traits' => [],
		],
		'FastyBird\\Module\\Triggers\\Models\\Entities\\Notifications\\NotificationsManager' => [
			'traits' => [],
		],
		'FastyBird\\Module\\Triggers\\Models\\Entities\\Notifications\\NotificationsRepository' => [
			'traits' => [],
		],
		'FastyBird\\Module\\Triggers\\Models\\Entities\\Triggers\\Controls\\ControlsManager' => [
			'traits' => [],
		],
		'FastyBird\\Module\\Triggers\\Models\\Entities\\Triggers\\Controls\\ControlsRepository' => [
			'traits' => [],
		],
		'FastyBird\\Module\\Triggers\\Models\\Entities\\Triggers\\TriggersManager' => [
			'traits' => [],
		],
		'FastyBird\\Module\\Triggers\\Models\\Entities\\Triggers\\TriggersRepository' => [
			'traits' => [],
		],
		'FastyBird\\Module\\Triggers\\Models\\States\\ActionsManager' => [
			'readonly' => true,
			'traits' => [],
		],
		'FastyBird\\Module\\Triggers\\Models\\States\\ActionsRepository' => [
			'readonly' => true,
			'traits' => [],
		],
		'FastyBird\\Module\\Triggers\\Models\\States\\ConditionsManager' => [
			'readonly' => true,
			'traits' => [],
		],
		'FastyBird\\Module\\Triggers\\Models\\States\\ConditionsRepository' => [
			'readonly' => true,
			'traits' => [],
		],
		'FastyBird\\Module\\Triggers\\Queries\\Entities\\FindNotifications' => [
			'final' => true,
		],
		'FastyBird\\Module\\Triggers\\Queries\\Entities\\FindTriggerControls' => [
			'final' => true,
		],
		'FastyBird\\Module\\Triggers\\Queries\\Entities\\FindTriggers' => [
			'final' => true,
		],
		'FastyBird\\Module\\Triggers\\Router\\ApiRoutes' => [
			'final' => true,
			'readonly' => true,
		],
		'FastyBird\\Module\\Triggers\\Router\\Validator::__construct()' => [
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
		'FastyBird\\Module\\Triggers\\Router\\Validator' => [
			'final' => true,
		],
		'FastyBird\\Module\\Triggers\\Subscribers\\ModuleEntities' => [
			'readonly' => true,
			'traits' => [],
		],
		'FastyBird\\Module\\Triggers\\Subscribers\\NotificationEntity' => [
			'traits' => [],
		],
		'FastyBird\\Module\\Triggers\\Utilities\\Api' => [
			'final' => true,
		],
	],
];
