<?php declare(strict_types = 1);

/**
 * #701 (E7.7, #462), Bridge/ShellyConnectorHomeKitConnector. The change list format is documented in tools/api-surface.php.
 *
 * public constants gain a type (the SCHEMA_TYPE and TYPE overrides of the HomeKit parents included,
 * D8), final and readonly classes, Nette\SmartObject removed from 2 classes.
 * Mapping\Characteristics\Characteristic is collapsed into BasicCharacteristic (census T2).
 * Hydrators\Devices\Shelly takes the schema container and the property hydrators instead of the DI
 * container (census T8). The Doctrine entities stay open.
 */

return [
	'removed' => [
		'FastyBird\\Bridge\\ShellyConnectorHomeKitConnector\\Builders\\Builder::__call()',
		'FastyBird\\Bridge\\ShellyConnectorHomeKitConnector\\Builders\\Builder::__callStatic()',
		'FastyBird\\Bridge\\ShellyConnectorHomeKitConnector\\Builders\\Builder::__get()',
		'FastyBird\\Bridge\\ShellyConnectorHomeKitConnector\\Builders\\Builder::__isset()',
		'FastyBird\\Bridge\\ShellyConnectorHomeKitConnector\\Builders\\Builder::__set()',
		'FastyBird\\Bridge\\ShellyConnectorHomeKitConnector\\Builders\\Builder::__unset()',
		'FastyBird\\Bridge\\ShellyConnectorHomeKitConnector\\Controllers\\BaseV1::__call()',
		'FastyBird\\Bridge\\ShellyConnectorHomeKitConnector\\Controllers\\BaseV1::__callStatic()',
		'FastyBird\\Bridge\\ShellyConnectorHomeKitConnector\\Controllers\\BaseV1::__get()',
		'FastyBird\\Bridge\\ShellyConnectorHomeKitConnector\\Controllers\\BaseV1::__isset()',
		'FastyBird\\Bridge\\ShellyConnectorHomeKitConnector\\Controllers\\BaseV1::__set()',
		'FastyBird\\Bridge\\ShellyConnectorHomeKitConnector\\Controllers\\BaseV1::__unset()',
		'FastyBird\\Bridge\\ShellyConnectorHomeKitConnector\\Mapping\\Characteristics\\Characteristic',
	],
	'changed' => [
		'FastyBird\\Bridge\\ShellyConnectorHomeKitConnector\\Builders\\Builder' => [
			'final' => true,
			'readonly' => true,
			'traits' => [],
		],
		'FastyBird\\Bridge\\ShellyConnectorHomeKitConnector\\Commands\\Build::NAME' => [
			'type' => 'string',
		],
		'FastyBird\\Bridge\\ShellyConnectorHomeKitConnector\\Commands\\Build' => [
			'final' => true,
		],
		'FastyBird\\Bridge\\ShellyConnectorHomeKitConnector\\Constants::MANUFACTURER' => [
			'type' => 'string',
		],
		'FastyBird\\Bridge\\ShellyConnectorHomeKitConnector\\Constants::MODEL' => [
			'type' => 'string',
		],
		'FastyBird\\Bridge\\ShellyConnectorHomeKitConnector\\Constants::RESOURCES_FOLDER' => [
			'type' => 'string',
		],
		'FastyBird\\Bridge\\ShellyConnectorHomeKitConnector\\Constants::ROUTE_NAME_BRIDGE' => [
			'type' => 'string',
		],
		'FastyBird\\Bridge\\ShellyConnectorHomeKitConnector\\Constants::ROUTE_NAME_BRIDGES' => [
			'type' => 'string',
		],
		'FastyBird\\Bridge\\ShellyConnectorHomeKitConnector\\Controllers\\BaseV1' => [
			'traits' => [],
		],
		'FastyBird\\Bridge\\ShellyConnectorHomeKitConnector\\Controllers\\BridgesV1' => [
			'final' => true,
		],
		'FastyBird\\Bridge\\ShellyConnectorHomeKitConnector\\DI\\ShellyConnectorHomeKitConnectorExtension::NAME' => [
			'type' => 'string',
		],
		'FastyBird\\Bridge\\ShellyConnectorHomeKitConnector\\DI\\ShellyConnectorHomeKitConnectorExtension::beforeCompile()' => [
			'throws' => [
				'Nette\\DI\\MissingServiceException',
				'Nette\\DI\\NotAllowedDuringResolvingException',
			],
		],
		'FastyBird\\Bridge\\ShellyConnectorHomeKitConnector\\DI\\ShellyConnectorHomeKitConnectorExtension' => [
			'final' => true,
		],
		'FastyBird\\Bridge\\ShellyConnectorHomeKitConnector\\Documents\\Channels\\InputButton' => [
			'final' => true,
		],
		'FastyBird\\Bridge\\ShellyConnectorHomeKitConnector\\Documents\\Channels\\InputSwitch' => [
			'final' => true,
		],
		'FastyBird\\Bridge\\ShellyConnectorHomeKitConnector\\Documents\\Channels\\Lightbulb' => [
			'final' => true,
		],
		'FastyBird\\Bridge\\ShellyConnectorHomeKitConnector\\Documents\\Channels\\Outlet' => [
			'final' => true,
		],
		'FastyBird\\Bridge\\ShellyConnectorHomeKitConnector\\Documents\\Channels\\Relay' => [
			'final' => true,
		],
		'FastyBird\\Bridge\\ShellyConnectorHomeKitConnector\\Documents\\Channels\\Valve' => [
			'final' => true,
		],
		'FastyBird\\Bridge\\ShellyConnectorHomeKitConnector\\Documents\\Channels\\WindowCovering' => [
			'final' => true,
		],
		'FastyBird\\Bridge\\ShellyConnectorHomeKitConnector\\Documents\\Devices\\Shelly' => [
			'final' => true,
		],
		'FastyBird\\Bridge\\ShellyConnectorHomeKitConnector\\Entities\\Channels\\InputButton::TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Bridge\\ShellyConnectorHomeKitConnector\\Entities\\Channels\\InputSwitch::TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Bridge\\ShellyConnectorHomeKitConnector\\Entities\\Channels\\Lightbulb::TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Bridge\\ShellyConnectorHomeKitConnector\\Entities\\Channels\\Outlet::TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Bridge\\ShellyConnectorHomeKitConnector\\Entities\\Channels\\Relay::TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Bridge\\ShellyConnectorHomeKitConnector\\Entities\\Channels\\Valve::TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Bridge\\ShellyConnectorHomeKitConnector\\Entities\\Channels\\WindowCovering::TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Bridge\\ShellyConnectorHomeKitConnector\\Entities\\Devices\\Shelly::TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Bridge\\ShellyConnectorHomeKitConnector\\Exceptions\\InvalidArgument' => [
			'final' => true,
		],
		'FastyBird\\Bridge\\ShellyConnectorHomeKitConnector\\Exceptions\\InvalidState' => [
			'final' => true,
		],
		'FastyBird\\Bridge\\ShellyConnectorHomeKitConnector\\Exceptions\\Runtime' => [
			'final' => true,
		],
		'FastyBird\\Bridge\\ShellyConnectorHomeKitConnector\\Hydrators\\Channels\\InputButton' => [
			'final' => true,
		],
		'FastyBird\\Bridge\\ShellyConnectorHomeKitConnector\\Hydrators\\Channels\\InputSwitch' => [
			'final' => true,
		],
		'FastyBird\\Bridge\\ShellyConnectorHomeKitConnector\\Hydrators\\Channels\\Lightbulb' => [
			'final' => true,
		],
		'FastyBird\\Bridge\\ShellyConnectorHomeKitConnector\\Hydrators\\Channels\\Outlet' => [
			'final' => true,
		],
		'FastyBird\\Bridge\\ShellyConnectorHomeKitConnector\\Hydrators\\Channels\\Relay' => [
			'final' => true,
		],
		'FastyBird\\Bridge\\ShellyConnectorHomeKitConnector\\Hydrators\\Channels\\Valve' => [
			'final' => true,
		],
		'FastyBird\\Bridge\\ShellyConnectorHomeKitConnector\\Hydrators\\Channels\\WindowCovering' => [
			'final' => true,
		],
		'FastyBird\\Bridge\\ShellyConnectorHomeKitConnector\\Hydrators\\Devices\\Shelly::__construct()' => [
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
			'parameters.$propertiesHydrators' => [
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
				'type' => 'Nette\\Localization\\Translator',
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
		'FastyBird\\Bridge\\ShellyConnectorHomeKitConnector\\Hydrators\\Devices\\Shelly::hydratePropertiesRelationship()' => [
			'throws' => [
				'FastyBird\\Core\\Api\\Exceptions\\JsonApiError',
				'FastyBird\\Core\\Exceptions\\InvalidState',
				'Throwable',
			],
		],
		'FastyBird\\Bridge\\ShellyConnectorHomeKitConnector\\Hydrators\\Devices\\Shelly' => [
			'final' => true,
		],
		'FastyBird\\Bridge\\ShellyConnectorHomeKitConnector\\Mapping\\Accessories\\InputAccessory' => [
			'final' => true,
		],
		'FastyBird\\Bridge\\ShellyConnectorHomeKitConnector\\Mapping\\Accessories\\LightAccessory' => [
			'final' => true,
		],
		'FastyBird\\Bridge\\ShellyConnectorHomeKitConnector\\Mapping\\Accessories\\RollerAccessory' => [
			'final' => true,
		],
		'FastyBird\\Bridge\\ShellyConnectorHomeKitConnector\\Mapping\\Accessories\\SwitchAccessory' => [
			'final' => true,
		],
		'FastyBird\\Bridge\\ShellyConnectorHomeKitConnector\\Mapping\\Characteristics\\BasicCharacteristic' => [
			'final' => true,
		],
		'FastyBird\\Bridge\\ShellyConnectorHomeKitConnector\\Mapping\\Gen1' => [
			'final' => true,
		],
		'FastyBird\\Bridge\\ShellyConnectorHomeKitConnector\\Mapping\\Gen2' => [
			'final' => true,
		],
		'FastyBird\\Bridge\\ShellyConnectorHomeKitConnector\\Mapping\\Services\\LightbulbService' => [
			'final' => true,
		],
		'FastyBird\\Bridge\\ShellyConnectorHomeKitConnector\\Mapping\\Services\\OutletService' => [
			'final' => true,
		],
		'FastyBird\\Bridge\\ShellyConnectorHomeKitConnector\\Mapping\\Services\\StatelessProgrammableSwitch' => [
			'final' => true,
		],
		'FastyBird\\Bridge\\ShellyConnectorHomeKitConnector\\Mapping\\Services\\SwitchService' => [
			'final' => true,
		],
		'FastyBird\\Bridge\\ShellyConnectorHomeKitConnector\\Mapping\\Services\\ValveService' => [
			'final' => true,
		],
		'FastyBird\\Bridge\\ShellyConnectorHomeKitConnector\\Mapping\\Services\\WindowCoveringService' => [
			'final' => true,
		],
		'FastyBird\\Bridge\\ShellyConnectorHomeKitConnector\\Protocol\\Accessories\\Shelly' => [
			'final' => true,
		],
		'FastyBird\\Bridge\\ShellyConnectorHomeKitConnector\\Protocol\\Accessories\\ShellyFactory' => [
			'final' => true,
		],
		'FastyBird\\Bridge\\ShellyConnectorHomeKitConnector\\Protocol\\Services\\InputButtonFactory' => [
			'final' => true,
		],
		'FastyBird\\Bridge\\ShellyConnectorHomeKitConnector\\Protocol\\Services\\InputSwitchFactory' => [
			'final' => true,
		],
		'FastyBird\\Bridge\\ShellyConnectorHomeKitConnector\\Protocol\\Services\\LightbulbFactory' => [
			'final' => true,
		],
		'FastyBird\\Bridge\\ShellyConnectorHomeKitConnector\\Protocol\\Services\\OutletFactory' => [
			'final' => true,
		],
		'FastyBird\\Bridge\\ShellyConnectorHomeKitConnector\\Protocol\\Services\\RelayFactory' => [
			'final' => true,
		],
		'FastyBird\\Bridge\\ShellyConnectorHomeKitConnector\\Protocol\\Services\\ValveFactory' => [
			'final' => true,
		],
		'FastyBird\\Bridge\\ShellyConnectorHomeKitConnector\\Protocol\\Services\\WindowCoveringFactory' => [
			'final' => true,
		],
		'FastyBird\\Bridge\\ShellyConnectorHomeKitConnector\\Queries\\Configuration\\FindShellyChannels' => [
			'final' => true,
		],
		'FastyBird\\Bridge\\ShellyConnectorHomeKitConnector\\Queries\\Configuration\\FindShellyDevices' => [
			'final' => true,
		],
		'FastyBird\\Bridge\\ShellyConnectorHomeKitConnector\\Queries\\Entities\\FindShellyChannels' => [
			'final' => true,
		],
		'FastyBird\\Bridge\\ShellyConnectorHomeKitConnector\\Queries\\Entities\\FindShellyDevices' => [
			'final' => true,
		],
		'FastyBird\\Bridge\\ShellyConnectorHomeKitConnector\\Router\\ApiRoutes::RELATION_ENTITY' => [
			'type' => 'string',
		],
		'FastyBird\\Bridge\\ShellyConnectorHomeKitConnector\\Router\\ApiRoutes::URL_ITEM_ID' => [
			'type' => 'string',
		],
		'FastyBird\\Bridge\\ShellyConnectorHomeKitConnector\\Router\\ApiRoutes' => [
			'final' => true,
			'readonly' => true,
		],
		'FastyBird\\Bridge\\ShellyConnectorHomeKitConnector\\Schemas\\Channels\\InputButton::SCHEMA_TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Bridge\\ShellyConnectorHomeKitConnector\\Schemas\\Channels\\InputSwitch::SCHEMA_TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Bridge\\ShellyConnectorHomeKitConnector\\Schemas\\Channels\\Lightbulb::SCHEMA_TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Bridge\\ShellyConnectorHomeKitConnector\\Schemas\\Channels\\Outlet::SCHEMA_TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Bridge\\ShellyConnectorHomeKitConnector\\Schemas\\Channels\\Relay::SCHEMA_TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Bridge\\ShellyConnectorHomeKitConnector\\Schemas\\Channels\\Valve::SCHEMA_TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Bridge\\ShellyConnectorHomeKitConnector\\Schemas\\Channels\\WindowCovering::SCHEMA_TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Bridge\\ShellyConnectorHomeKitConnector\\Schemas\\Devices\\Shelly::SCHEMA_TYPE' => [
			'type' => 'string',
		],
	],
];
