<?php declare(strict_types = 1);

/**
 * #700 (E7.6, #462): the API change list of the Ui module. The format is documented in
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
		'FastyBird\\Module\\Ui\\Controllers\\Finders\\TDashboard' => 'FastyBird\\Module\\Ui\\Controllers\\Finders\\FindsDashboard',
		'FastyBird\\Module\\Ui\\Controllers\\Finders\\TGroup' => 'FastyBird\\Module\\Ui\\Controllers\\Finders\\FindsGroup',
		'FastyBird\\Module\\Ui\\Controllers\\Finders\\TTab' => 'FastyBird\\Module\\Ui\\Controllers\\Finders\\FindsTab',
		'FastyBird\\Module\\Ui\\Controllers\\Finders\\TWidget' => 'FastyBird\\Module\\Ui\\Controllers\\Finders\\FindsWidget',
		'FastyBird\\Module\\Ui\\Entities\\TEntity' => 'FastyBird\\Module\\Ui\\Entities\\HasEntityId',
		'FastyBird\\Module\\Ui\\Entities\\TEntityParams' => 'FastyBird\\Module\\Ui\\Entities\\HasEntityParams',
		'FastyBird\\Module\\Ui\\Entities\\Widgets\\Displays\\Parameters\\TIcon' => 'FastyBird\\Module\\Ui\\Entities\\Widgets\\Displays\\Parameters\\HasIcon',
		'FastyBird\\Module\\Ui\\Entities\\Widgets\\Displays\\Parameters\\TMaximumValue' => 'FastyBird\\Module\\Ui\\Entities\\Widgets\\Displays\\Parameters\\HasMaximumValue',
		'FastyBird\\Module\\Ui\\Entities\\Widgets\\Displays\\Parameters\\TMinimumValue' => 'FastyBird\\Module\\Ui\\Entities\\Widgets\\Displays\\Parameters\\HasMinimumValue',
		'FastyBird\\Module\\Ui\\Entities\\Widgets\\Displays\\Parameters\\TPrecision' => 'FastyBird\\Module\\Ui\\Entities\\Widgets\\Displays\\Parameters\\HasPrecision',
		'FastyBird\\Module\\Ui\\Entities\\Widgets\\Displays\\Parameters\\TStepValue' => 'FastyBird\\Module\\Ui\\Entities\\Widgets\\Displays\\Parameters\\HasStepValue',
	],
	'removed' => [
		'FastyBird\\Module\\Ui\\Controllers\\BaseV1::__call()',
		'FastyBird\\Module\\Ui\\Controllers\\BaseV1::__callStatic()',
		'FastyBird\\Module\\Ui\\Controllers\\BaseV1::__get()',
		'FastyBird\\Module\\Ui\\Controllers\\BaseV1::__isset()',
		'FastyBird\\Module\\Ui\\Controllers\\BaseV1::__set()',
		'FastyBird\\Module\\Ui\\Controllers\\BaseV1::__unset()',
		'FastyBird\\Module\\Ui\\Models\\Entities\\Dashboards\\Manager::__call()',
		'FastyBird\\Module\\Ui\\Models\\Entities\\Dashboards\\Manager::__callStatic()',
		'FastyBird\\Module\\Ui\\Models\\Entities\\Dashboards\\Manager::__get()',
		'FastyBird\\Module\\Ui\\Models\\Entities\\Dashboards\\Manager::__isset()',
		'FastyBird\\Module\\Ui\\Models\\Entities\\Dashboards\\Manager::__set()',
		'FastyBird\\Module\\Ui\\Models\\Entities\\Dashboards\\Manager::__unset()',
		'FastyBird\\Module\\Ui\\Models\\Entities\\Dashboards\\Repository::__call()',
		'FastyBird\\Module\\Ui\\Models\\Entities\\Dashboards\\Repository::__callStatic()',
		'FastyBird\\Module\\Ui\\Models\\Entities\\Dashboards\\Repository::__get()',
		'FastyBird\\Module\\Ui\\Models\\Entities\\Dashboards\\Repository::__isset()',
		'FastyBird\\Module\\Ui\\Models\\Entities\\Dashboards\\Repository::__set()',
		'FastyBird\\Module\\Ui\\Models\\Entities\\Dashboards\\Repository::__unset()',
		'FastyBird\\Module\\Ui\\Models\\Entities\\Dashboards\\Tabs\\Manager::__call()',
		'FastyBird\\Module\\Ui\\Models\\Entities\\Dashboards\\Tabs\\Manager::__callStatic()',
		'FastyBird\\Module\\Ui\\Models\\Entities\\Dashboards\\Tabs\\Manager::__get()',
		'FastyBird\\Module\\Ui\\Models\\Entities\\Dashboards\\Tabs\\Manager::__isset()',
		'FastyBird\\Module\\Ui\\Models\\Entities\\Dashboards\\Tabs\\Manager::__set()',
		'FastyBird\\Module\\Ui\\Models\\Entities\\Dashboards\\Tabs\\Manager::__unset()',
		'FastyBird\\Module\\Ui\\Models\\Entities\\Dashboards\\Tabs\\Repository::__call()',
		'FastyBird\\Module\\Ui\\Models\\Entities\\Dashboards\\Tabs\\Repository::__callStatic()',
		'FastyBird\\Module\\Ui\\Models\\Entities\\Dashboards\\Tabs\\Repository::__get()',
		'FastyBird\\Module\\Ui\\Models\\Entities\\Dashboards\\Tabs\\Repository::__isset()',
		'FastyBird\\Module\\Ui\\Models\\Entities\\Dashboards\\Tabs\\Repository::__set()',
		'FastyBird\\Module\\Ui\\Models\\Entities\\Dashboards\\Tabs\\Repository::__unset()',
		'FastyBird\\Module\\Ui\\Models\\Entities\\Groups\\Manager::__call()',
		'FastyBird\\Module\\Ui\\Models\\Entities\\Groups\\Manager::__callStatic()',
		'FastyBird\\Module\\Ui\\Models\\Entities\\Groups\\Manager::__get()',
		'FastyBird\\Module\\Ui\\Models\\Entities\\Groups\\Manager::__isset()',
		'FastyBird\\Module\\Ui\\Models\\Entities\\Groups\\Manager::__set()',
		'FastyBird\\Module\\Ui\\Models\\Entities\\Groups\\Manager::__unset()',
		'FastyBird\\Module\\Ui\\Models\\Entities\\Groups\\Repository::__call()',
		'FastyBird\\Module\\Ui\\Models\\Entities\\Groups\\Repository::__callStatic()',
		'FastyBird\\Module\\Ui\\Models\\Entities\\Groups\\Repository::__get()',
		'FastyBird\\Module\\Ui\\Models\\Entities\\Groups\\Repository::__isset()',
		'FastyBird\\Module\\Ui\\Models\\Entities\\Groups\\Repository::__set()',
		'FastyBird\\Module\\Ui\\Models\\Entities\\Groups\\Repository::__unset()',
		'FastyBird\\Module\\Ui\\Models\\Entities\\Widgets\\DataSources\\Manager::__call()',
		'FastyBird\\Module\\Ui\\Models\\Entities\\Widgets\\DataSources\\Manager::__callStatic()',
		'FastyBird\\Module\\Ui\\Models\\Entities\\Widgets\\DataSources\\Manager::__get()',
		'FastyBird\\Module\\Ui\\Models\\Entities\\Widgets\\DataSources\\Manager::__isset()',
		'FastyBird\\Module\\Ui\\Models\\Entities\\Widgets\\DataSources\\Manager::__set()',
		'FastyBird\\Module\\Ui\\Models\\Entities\\Widgets\\DataSources\\Manager::__unset()',
		'FastyBird\\Module\\Ui\\Models\\Entities\\Widgets\\DataSources\\Repository::__call()',
		'FastyBird\\Module\\Ui\\Models\\Entities\\Widgets\\DataSources\\Repository::__callStatic()',
		'FastyBird\\Module\\Ui\\Models\\Entities\\Widgets\\DataSources\\Repository::__get()',
		'FastyBird\\Module\\Ui\\Models\\Entities\\Widgets\\DataSources\\Repository::__isset()',
		'FastyBird\\Module\\Ui\\Models\\Entities\\Widgets\\DataSources\\Repository::__set()',
		'FastyBird\\Module\\Ui\\Models\\Entities\\Widgets\\DataSources\\Repository::__unset()',
		'FastyBird\\Module\\Ui\\Models\\Entities\\Widgets\\Displays\\Manager::__call()',
		'FastyBird\\Module\\Ui\\Models\\Entities\\Widgets\\Displays\\Manager::__callStatic()',
		'FastyBird\\Module\\Ui\\Models\\Entities\\Widgets\\Displays\\Manager::__get()',
		'FastyBird\\Module\\Ui\\Models\\Entities\\Widgets\\Displays\\Manager::__isset()',
		'FastyBird\\Module\\Ui\\Models\\Entities\\Widgets\\Displays\\Manager::__set()',
		'FastyBird\\Module\\Ui\\Models\\Entities\\Widgets\\Displays\\Manager::__unset()',
		'FastyBird\\Module\\Ui\\Models\\Entities\\Widgets\\Displays\\Repository::__call()',
		'FastyBird\\Module\\Ui\\Models\\Entities\\Widgets\\Displays\\Repository::__callStatic()',
		'FastyBird\\Module\\Ui\\Models\\Entities\\Widgets\\Displays\\Repository::__get()',
		'FastyBird\\Module\\Ui\\Models\\Entities\\Widgets\\Displays\\Repository::__isset()',
		'FastyBird\\Module\\Ui\\Models\\Entities\\Widgets\\Displays\\Repository::__set()',
		'FastyBird\\Module\\Ui\\Models\\Entities\\Widgets\\Displays\\Repository::__unset()',
		'FastyBird\\Module\\Ui\\Models\\Entities\\Widgets\\Manager::__call()',
		'FastyBird\\Module\\Ui\\Models\\Entities\\Widgets\\Manager::__callStatic()',
		'FastyBird\\Module\\Ui\\Models\\Entities\\Widgets\\Manager::__get()',
		'FastyBird\\Module\\Ui\\Models\\Entities\\Widgets\\Manager::__isset()',
		'FastyBird\\Module\\Ui\\Models\\Entities\\Widgets\\Manager::__set()',
		'FastyBird\\Module\\Ui\\Models\\Entities\\Widgets\\Manager::__unset()',
		'FastyBird\\Module\\Ui\\Models\\Entities\\Widgets\\Repository::__call()',
		'FastyBird\\Module\\Ui\\Models\\Entities\\Widgets\\Repository::__callStatic()',
		'FastyBird\\Module\\Ui\\Models\\Entities\\Widgets\\Repository::__get()',
		'FastyBird\\Module\\Ui\\Models\\Entities\\Widgets\\Repository::__isset()',
		'FastyBird\\Module\\Ui\\Models\\Entities\\Widgets\\Repository::__set()',
		'FastyBird\\Module\\Ui\\Models\\Entities\\Widgets\\Repository::__unset()',
		'FastyBird\\Module\\Ui\\Subscribers\\ConfigurationCache::__call()',
		'FastyBird\\Module\\Ui\\Subscribers\\ConfigurationCache::__callStatic()',
		'FastyBird\\Module\\Ui\\Subscribers\\ConfigurationCache::__get()',
		'FastyBird\\Module\\Ui\\Subscribers\\ConfigurationCache::__isset()',
		'FastyBird\\Module\\Ui\\Subscribers\\ConfigurationCache::__set()',
		'FastyBird\\Module\\Ui\\Subscribers\\ConfigurationCache::__unset()',
		'FastyBird\\Module\\Ui\\Subscribers\\DashboardEntity::__call()',
		'FastyBird\\Module\\Ui\\Subscribers\\DashboardEntity::__callStatic()',
		'FastyBird\\Module\\Ui\\Subscribers\\DashboardEntity::__get()',
		'FastyBird\\Module\\Ui\\Subscribers\\DashboardEntity::__isset()',
		'FastyBird\\Module\\Ui\\Subscribers\\DashboardEntity::__set()',
		'FastyBird\\Module\\Ui\\Subscribers\\DashboardEntity::__unset()',
		'FastyBird\\Module\\Ui\\Subscribers\\ModuleEntities::__call()',
		'FastyBird\\Module\\Ui\\Subscribers\\ModuleEntities::__callStatic()',
		'FastyBird\\Module\\Ui\\Subscribers\\ModuleEntities::__get()',
		'FastyBird\\Module\\Ui\\Subscribers\\ModuleEntities::__isset()',
		'FastyBird\\Module\\Ui\\Subscribers\\ModuleEntities::__set()',
		'FastyBird\\Module\\Ui\\Subscribers\\ModuleEntities::__unset()',
	],
	'changed' => [
		'FastyBird\\Module\\Ui\\Caching\\Container' => [
			'final' => true,
		],
		'FastyBird\\Module\\Ui\\Commands\\Install' => [
			'final' => true,
		],
		'FastyBird\\Module\\Ui\\Controllers\\BaseV1' => [
			'traits' => [],
		],
		'FastyBird\\Module\\Ui\\DI\\UiExtension::beforeCompile()' => [
			'throws' => [
				'Nette\\DI\\MissingServiceException',
				'Nette\\DI\\NotAllowedDuringResolvingException',
			],
		],
		'FastyBird\\Module\\Ui\\DI\\UiExtension' => [
			'final' => true,
		],
		'FastyBird\\Module\\Ui\\Documents\\Dashboards\\Dashboard' => [
			'readonly' => true,
		],
		'FastyBird\\Module\\Ui\\Documents\\Dashboards\\Tabs\\Tab' => [
			'readonly' => true,
		],
		'FastyBird\\Module\\Ui\\Documents\\Groups\\Group' => [
			'readonly' => true,
		],
		'FastyBird\\Module\\Ui\\Documents\\Widgets\\AnalogActuator' => [
			'final' => true,
		],
		'FastyBird\\Module\\Ui\\Documents\\Widgets\\AnalogSensor' => [
			'final' => true,
		],
		'FastyBird\\Module\\Ui\\Documents\\Widgets\\DataSources\\Generic' => [
			'final' => true,
		],
		'FastyBird\\Module\\Ui\\Documents\\Widgets\\DigitalActuator' => [
			'final' => true,
		],
		'FastyBird\\Module\\Ui\\Documents\\Widgets\\DigitalSensor' => [
			'final' => true,
		],
		'FastyBird\\Module\\Ui\\Documents\\Widgets\\Displays\\AnalogValue' => [
			'final' => true,
		],
		'FastyBird\\Module\\Ui\\Documents\\Widgets\\Displays\\Button' => [
			'final' => true,
		],
		'FastyBird\\Module\\Ui\\Documents\\Widgets\\Displays\\ChartGraph' => [
			'final' => true,
		],
		'FastyBird\\Module\\Ui\\Documents\\Widgets\\Displays\\DigitalValue' => [
			'final' => true,
		],
		'FastyBird\\Module\\Ui\\Documents\\Widgets\\Displays\\Gauge' => [
			'final' => true,
		],
		'FastyBird\\Module\\Ui\\Documents\\Widgets\\Displays\\GroupedButton' => [
			'final' => true,
		],
		'FastyBird\\Module\\Ui\\Documents\\Widgets\\Displays\\Slider' => [
			'final' => true,
		],
		'FastyBird\\Module\\Ui\\Events\\ActionCommandReceived' => [
			'final' => true,
		],
		'FastyBird\\Module\\Ui\\Events\\EntityCreated' => [
			'final' => true,
		],
		'FastyBird\\Module\\Ui\\Events\\EntityDeleted' => [
			'final' => true,
		],
		'FastyBird\\Module\\Ui\\Events\\EntityUpdated' => [
			'final' => true,
		],
		'FastyBird\\Module\\Ui\\Exceptions\\InvalidArgument' => [
			'final' => true,
		],
		'FastyBird\\Module\\Ui\\Exceptions\\InvalidState' => [
			'final' => true,
		],
		'FastyBird\\Module\\Ui\\Exceptions\\Runtime' => [
			'final' => true,
		],
		'FastyBird\\Module\\Ui\\Hydrators\\Widgets\\Widget::__construct()' => [
			'-parameters.$container' => null,
			'-parameters.$managerRegistry' => null,
			'-parameters.$translator' => null,
			'-parameters.$crudReader' => null,
			'parameters.$schemaContainer' => [
				'position' => 2,
				'type' => 'FastyBird\\Core\\Api\\Encoding\\SchemaContainer',
				'optional' => false,
				'default' => null,
				'defaultConstant' => null,
				'variadic' => false,
				'byRef' => false,
				'promoted' => true,
				'attributes' => [],
			],
			'parameters.$dataSourcesHydrators' => [
				'position' => 3,
				'type' => 'array',
				'optional' => false,
				'default' => null,
				'defaultConstant' => null,
				'variadic' => false,
				'byRef' => false,
				'promoted' => true,
				'attributes' => [],
			],
			'parameters.$managerRegistry' => [
				'position' => 4,
				'type' => 'Doctrine\\Persistence\\ManagerRegistry',
				'optional' => false,
				'default' => null,
				'defaultConstant' => null,
				'variadic' => false,
				'byRef' => false,
				'promoted' => false,
				'attributes' => [],
			],
			'parameters.$translator' => [
				'position' => 5,
				'type' => 'Contributte\\Translation\\Translator',
				'optional' => false,
				'default' => null,
				'defaultConstant' => null,
				'variadic' => false,
				'byRef' => false,
				'promoted' => false,
				'attributes' => [],
			],
			'parameters.$crudReader' => [
				'position' => 6,
				'type' => '?FastyBird\\Core\\Api\\Helpers\\CrudReader',
				'optional' => true,
				'default' => 'null',
				'defaultConstant' => null,
				'variadic' => false,
				'byRef' => false,
				'promoted' => false,
				'attributes' => [],
			],
		],
		'FastyBird\\Module\\Ui\\Hydrators\\Widgets\\Widget::hydrateDataSourcesRelationship()' => [
			'throws' => [
				'FastyBird\\Core\\Api\\Exceptions\\JsonApiError',
				'FastyBird\\Core\\Exceptions\\InvalidArgument',
				'FastyBird\\Core\\Exceptions\\InvalidState',
				'FastyBird\\Core\\Exceptions\\Runtime',
				'Ramsey\\Uuid\\Exception\\InvalidArgumentException',
			],
		],
		'FastyBird\\Module\\Ui\\Middleware\\Access' => [
			'readonly' => true,
		],
		'FastyBird\\Module\\Ui\\Models\\Entities\\Dashboards\\Manager' => [
			'traits' => [],
		],
		'FastyBird\\Module\\Ui\\Models\\Entities\\Dashboards\\Repository' => [
			'traits' => [],
		],
		'FastyBird\\Module\\Ui\\Models\\Entities\\Dashboards\\Tabs\\Manager' => [
			'traits' => [],
		],
		'FastyBird\\Module\\Ui\\Models\\Entities\\Dashboards\\Tabs\\Repository' => [
			'traits' => [],
		],
		'FastyBird\\Module\\Ui\\Models\\Entities\\Groups\\Manager' => [
			'traits' => [],
		],
		'FastyBird\\Module\\Ui\\Models\\Entities\\Groups\\Repository' => [
			'traits' => [],
		],
		'FastyBird\\Module\\Ui\\Models\\Entities\\Widgets\\DataSources\\Manager' => [
			'final' => true,
			'traits' => [],
		],
		'FastyBird\\Module\\Ui\\Models\\Entities\\Widgets\\DataSources\\Repository' => [
			'traits' => [],
		],
		'FastyBird\\Module\\Ui\\Models\\Entities\\Widgets\\Displays\\Manager' => [
			'final' => true,
			'traits' => [],
		],
		'FastyBird\\Module\\Ui\\Models\\Entities\\Widgets\\Displays\\Repository' => [
			'traits' => [],
		],
		'FastyBird\\Module\\Ui\\Models\\Entities\\Widgets\\Manager' => [
			'traits' => [],
		],
		'FastyBird\\Module\\Ui\\Models\\Entities\\Widgets\\Repository' => [
			'traits' => [],
		],
		'FastyBird\\Module\\Ui\\Queries\\Configuration\\FindDashboardTabs' => [
			'final' => true,
		],
		'FastyBird\\Module\\Ui\\Queries\\Configuration\\FindDashboards' => [
			'final' => true,
		],
		'FastyBird\\Module\\Ui\\Queries\\Configuration\\FindGroups' => [
			'final' => true,
		],
		'FastyBird\\Module\\Ui\\Queries\\Configuration\\FindWidgetDisplays' => [
			'final' => true,
		],
		'FastyBird\\Module\\Ui\\Queries\\Configuration\\FindWidgets' => [
			'final' => true,
		],
		'FastyBird\\Module\\Ui\\Queries\\Entities\\FindDashboardTabs' => [
			'final' => true,
		],
		'FastyBird\\Module\\Ui\\Queries\\Entities\\FindDashboards' => [
			'final' => true,
		],
		'FastyBird\\Module\\Ui\\Queries\\Entities\\FindGroups' => [
			'final' => true,
		],
		'FastyBird\\Module\\Ui\\Queries\\Entities\\FindWidgetDisplays' => [
			'final' => true,
		],
		'FastyBird\\Module\\Ui\\Queries\\Entities\\FindWidgets' => [
			'final' => true,
		],
		'FastyBird\\Module\\Ui\\Router\\ApiRoutes' => [
			'final' => true,
			'readonly' => true,
		],
		'FastyBird\\Module\\Ui\\Router\\SocketRoutes' => [
			'final' => true,
		],
		'FastyBird\\Module\\Ui\\Router\\Validator::__construct()' => [
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
		'FastyBird\\Module\\Ui\\Router\\Validator' => [
			'final' => true,
		],
		'FastyBird\\Module\\Ui\\Subscribers\\ConfigurationCache' => [
			'readonly' => true,
			'traits' => [],
		],
		'FastyBird\\Module\\Ui\\Subscribers\\DashboardEntity' => [
			'readonly' => true,
			'traits' => [],
		],
		'FastyBird\\Module\\Ui\\Subscribers\\ModuleEntities' => [
			'readonly' => true,
			'traits' => [],
		],
		'FastyBird\\Module\\Ui\\Utilities\\Api' => [
			'final' => true,
		],
	],
];
