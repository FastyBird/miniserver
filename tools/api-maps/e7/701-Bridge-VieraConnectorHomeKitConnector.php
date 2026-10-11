<?php declare(strict_types = 1);

/**
 * #701 (E7.7, #462), Bridge/VieraConnectorHomeKitConnector. The change list format is documented in tools/api-surface.php.
 *
 * public constants gain a type (the SCHEMA_TYPE and TYPE overrides of the HomeKit parents included,
 * D8), final and readonly classes, Nette\SmartObject removed from 2 classes.
 * Mapping\Characteristics\Characteristic is collapsed into BasicCharacteristic (census T2). The
 * Doctrine entities stay open.
 */

return [
	'removed' => [
		'FastyBird\\Bridge\\VieraConnectorHomeKitConnector\\Builders\\Builder::__call()',
		'FastyBird\\Bridge\\VieraConnectorHomeKitConnector\\Builders\\Builder::__callStatic()',
		'FastyBird\\Bridge\\VieraConnectorHomeKitConnector\\Builders\\Builder::__get()',
		'FastyBird\\Bridge\\VieraConnectorHomeKitConnector\\Builders\\Builder::__isset()',
		'FastyBird\\Bridge\\VieraConnectorHomeKitConnector\\Builders\\Builder::__set()',
		'FastyBird\\Bridge\\VieraConnectorHomeKitConnector\\Builders\\Builder::__unset()',
		'FastyBird\\Bridge\\VieraConnectorHomeKitConnector\\Controllers\\BaseV1::__call()',
		'FastyBird\\Bridge\\VieraConnectorHomeKitConnector\\Controllers\\BaseV1::__callStatic()',
		'FastyBird\\Bridge\\VieraConnectorHomeKitConnector\\Controllers\\BaseV1::__get()',
		'FastyBird\\Bridge\\VieraConnectorHomeKitConnector\\Controllers\\BaseV1::__isset()',
		'FastyBird\\Bridge\\VieraConnectorHomeKitConnector\\Controllers\\BaseV1::__set()',
		'FastyBird\\Bridge\\VieraConnectorHomeKitConnector\\Controllers\\BaseV1::__unset()',
		'FastyBird\\Bridge\\VieraConnectorHomeKitConnector\\Mapping\\Characteristics\\Characteristic',
	],
	'changed' => [
		'FastyBird\\Bridge\\VieraConnectorHomeKitConnector\\Builders\\Builder' => [
			'final' => true,
			'readonly' => true,
			'traits' => [],
		],
		'FastyBird\\Bridge\\VieraConnectorHomeKitConnector\\Commands\\Build::NAME' => [
			'type' => 'string',
		],
		'FastyBird\\Bridge\\VieraConnectorHomeKitConnector\\Commands\\Build' => [
			'final' => true,
		],
		'FastyBird\\Bridge\\VieraConnectorHomeKitConnector\\Constants::DEFAULT_ACTIVE_CODE' => [
			'type' => 'int',
		],
		'FastyBird\\Bridge\\VieraConnectorHomeKitConnector\\Constants::DEFAULT_ACTIVE_IDENTIFIER' => [
			'type' => 'string',
		],
		'FastyBird\\Bridge\\VieraConnectorHomeKitConnector\\Constants::INPUT_SOURCE_TYPE_APPLICATION' => [
			'type' => 'int',
		],
		'FastyBird\\Bridge\\VieraConnectorHomeKitConnector\\Constants::INPUT_SOURCE_TYPE_HDMI' => [
			'type' => 'int',
		],
		'FastyBird\\Bridge\\VieraConnectorHomeKitConnector\\Constants::INPUT_SOURCE_TYPE_TV' => [
			'type' => 'int',
		],
		'FastyBird\\Bridge\\VieraConnectorHomeKitConnector\\Constants::MANUFACTURER' => [
			'type' => 'string',
		],
		'FastyBird\\Bridge\\VieraConnectorHomeKitConnector\\Constants::MODEL' => [
			'type' => 'string',
		],
		'FastyBird\\Bridge\\VieraConnectorHomeKitConnector\\Constants::RESOURCES_FOLDER' => [
			'type' => 'string',
		],
		'FastyBird\\Bridge\\VieraConnectorHomeKitConnector\\Constants::ROUTE_NAME_BRIDGE' => [
			'type' => 'string',
		],
		'FastyBird\\Bridge\\VieraConnectorHomeKitConnector\\Constants::ROUTE_NAME_BRIDGES' => [
			'type' => 'string',
		],
		'FastyBird\\Bridge\\VieraConnectorHomeKitConnector\\Controllers\\BaseV1' => [
			'traits' => [],
		],
		'FastyBird\\Bridge\\VieraConnectorHomeKitConnector\\Controllers\\BridgesV1' => [
			'final' => true,
		],
		'FastyBird\\Bridge\\VieraConnectorHomeKitConnector\\DI\\VieraConnectorHomeKitConnectorExtension::NAME' => [
			'type' => 'string',
		],
		'FastyBird\\Bridge\\VieraConnectorHomeKitConnector\\DI\\VieraConnectorHomeKitConnectorExtension' => [
			'final' => true,
		],
		'FastyBird\\Bridge\\VieraConnectorHomeKitConnector\\Documents\\Channels\\InputSource' => [
			'final' => true,
		],
		'FastyBird\\Bridge\\VieraConnectorHomeKitConnector\\Documents\\Channels\\Television' => [
			'final' => true,
		],
		'FastyBird\\Bridge\\VieraConnectorHomeKitConnector\\Documents\\Channels\\TelevisionSpeaker' => [
			'final' => true,
		],
		'FastyBird\\Bridge\\VieraConnectorHomeKitConnector\\Documents\\Devices\\Viera' => [
			'final' => true,
		],
		'FastyBird\\Bridge\\VieraConnectorHomeKitConnector\\Entities\\Channels\\InputSource::TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Bridge\\VieraConnectorHomeKitConnector\\Entities\\Channels\\Television::TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Bridge\\VieraConnectorHomeKitConnector\\Entities\\Channels\\TelevisionSpeaker::TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Bridge\\VieraConnectorHomeKitConnector\\Entities\\Devices\\Viera::TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Bridge\\VieraConnectorHomeKitConnector\\Exceptions\\InvalidArgument' => [
			'final' => true,
		],
		'FastyBird\\Bridge\\VieraConnectorHomeKitConnector\\Exceptions\\InvalidState' => [
			'final' => true,
		],
		'FastyBird\\Bridge\\VieraConnectorHomeKitConnector\\Exceptions\\Runtime' => [
			'final' => true,
		],
		'FastyBird\\Bridge\\VieraConnectorHomeKitConnector\\Hydrators\\Channels\\InputSource' => [
			'final' => true,
		],
		'FastyBird\\Bridge\\VieraConnectorHomeKitConnector\\Hydrators\\Channels\\Television' => [
			'final' => true,
		],
		'FastyBird\\Bridge\\VieraConnectorHomeKitConnector\\Hydrators\\Channels\\TelevisionSpeaker' => [
			'final' => true,
		],
		'FastyBird\\Bridge\\VieraConnectorHomeKitConnector\\Hydrators\\Devices\\Viera' => [
			'final' => true,
		],
		'FastyBird\\Bridge\\VieraConnectorHomeKitConnector\\Mapping\\Characteristics\\BasicCharacteristic' => [
			'final' => true,
		],
		'FastyBird\\Bridge\\VieraConnectorHomeKitConnector\\Mapping\\Services' => [
			'final' => true,
		],
		'FastyBird\\Bridge\\VieraConnectorHomeKitConnector\\Mapping\\Services\\InputSource' => [
			'final' => true,
		],
		'FastyBird\\Bridge\\VieraConnectorHomeKitConnector\\Mapping\\Services\\Television' => [
			'final' => true,
		],
		'FastyBird\\Bridge\\VieraConnectorHomeKitConnector\\Mapping\\Services\\TelevisionSpeaker' => [
			'final' => true,
		],
		'FastyBird\\Bridge\\VieraConnectorHomeKitConnector\\Protocol\\Accessories\\Viera' => [
			'final' => true,
		],
		'FastyBird\\Bridge\\VieraConnectorHomeKitConnector\\Protocol\\Accessories\\VieraFactory' => [
			'final' => true,
		],
		'FastyBird\\Bridge\\VieraConnectorHomeKitConnector\\Protocol\\Services\\InputSourceFactory' => [
			'final' => true,
		],
		'FastyBird\\Bridge\\VieraConnectorHomeKitConnector\\Protocol\\Services\\TelevisionFactory' => [
			'final' => true,
		],
		'FastyBird\\Bridge\\VieraConnectorHomeKitConnector\\Protocol\\Services\\TelevisionSpeakerFactory' => [
			'final' => true,
		],
		'FastyBird\\Bridge\\VieraConnectorHomeKitConnector\\Queries\\Configuration\\FindVieraChannels' => [
			'final' => true,
		],
		'FastyBird\\Bridge\\VieraConnectorHomeKitConnector\\Queries\\Configuration\\FindVieraDevices' => [
			'final' => true,
		],
		'FastyBird\\Bridge\\VieraConnectorHomeKitConnector\\Queries\\Entities\\FindVieraChannels' => [
			'final' => true,
		],
		'FastyBird\\Bridge\\VieraConnectorHomeKitConnector\\Queries\\Entities\\FindVieraDevices' => [
			'final' => true,
		],
		'FastyBird\\Bridge\\VieraConnectorHomeKitConnector\\Router\\ApiRoutes::RELATION_ENTITY' => [
			'type' => 'string',
		],
		'FastyBird\\Bridge\\VieraConnectorHomeKitConnector\\Router\\ApiRoutes::URL_ITEM_ID' => [
			'type' => 'string',
		],
		'FastyBird\\Bridge\\VieraConnectorHomeKitConnector\\Router\\ApiRoutes' => [
			'final' => true,
			'readonly' => true,
		],
		'FastyBird\\Bridge\\VieraConnectorHomeKitConnector\\Schemas\\Channels\\InputSource::SCHEMA_TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Bridge\\VieraConnectorHomeKitConnector\\Schemas\\Channels\\Television::SCHEMA_TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Bridge\\VieraConnectorHomeKitConnector\\Schemas\\Channels\\TelevisionSpeaker::SCHEMA_TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Bridge\\VieraConnectorHomeKitConnector\\Schemas\\Devices\\Viera::SCHEMA_TYPE' => [
			'type' => 'string',
		],
	],
];
