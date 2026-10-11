<?php declare(strict_types = 1);

/**
 * #701 (E7.7, #462), Bridge/DevicesModuleUiModule. The change list format is documented in tools/api-surface.php.
 *
 * public constants gain a type, final and readonly classes, and Nette\SmartObject removed from 4
 * classes. The Doctrine data source entities stay open. Exceptions\Exception (no implementer, no catch
 * site; D6) is deleted.
 */

return [
	'removed' => [
		'FastyBird\\Bridge\\DevicesModuleUiModule\\Exceptions\\Exception',
		'FastyBird\\Bridge\\DevicesModuleUiModule\\Subscribers\\ActionCommand::__call()',
		'FastyBird\\Bridge\\DevicesModuleUiModule\\Subscribers\\ActionCommand::__callStatic()',
		'FastyBird\\Bridge\\DevicesModuleUiModule\\Subscribers\\ActionCommand::__get()',
		'FastyBird\\Bridge\\DevicesModuleUiModule\\Subscribers\\ActionCommand::__isset()',
		'FastyBird\\Bridge\\DevicesModuleUiModule\\Subscribers\\ActionCommand::__set()',
		'FastyBird\\Bridge\\DevicesModuleUiModule\\Subscribers\\ActionCommand::__unset()',
		'FastyBird\\Bridge\\DevicesModuleUiModule\\Subscribers\\DocumentsMapper::__call()',
		'FastyBird\\Bridge\\DevicesModuleUiModule\\Subscribers\\DocumentsMapper::__callStatic()',
		'FastyBird\\Bridge\\DevicesModuleUiModule\\Subscribers\\DocumentsMapper::__get()',
		'FastyBird\\Bridge\\DevicesModuleUiModule\\Subscribers\\DocumentsMapper::__isset()',
		'FastyBird\\Bridge\\DevicesModuleUiModule\\Subscribers\\DocumentsMapper::__set()',
		'FastyBird\\Bridge\\DevicesModuleUiModule\\Subscribers\\DocumentsMapper::__unset()',
		'FastyBird\\Bridge\\DevicesModuleUiModule\\Subscribers\\ModuleEntities::__call()',
		'FastyBird\\Bridge\\DevicesModuleUiModule\\Subscribers\\ModuleEntities::__callStatic()',
		'FastyBird\\Bridge\\DevicesModuleUiModule\\Subscribers\\ModuleEntities::__get()',
		'FastyBird\\Bridge\\DevicesModuleUiModule\\Subscribers\\ModuleEntities::__isset()',
		'FastyBird\\Bridge\\DevicesModuleUiModule\\Subscribers\\ModuleEntities::__set()',
		'FastyBird\\Bridge\\DevicesModuleUiModule\\Subscribers\\ModuleEntities::__unset()',
		'FastyBird\\Bridge\\DevicesModuleUiModule\\Subscribers\\StateEntities::__call()',
		'FastyBird\\Bridge\\DevicesModuleUiModule\\Subscribers\\StateEntities::__callStatic()',
		'FastyBird\\Bridge\\DevicesModuleUiModule\\Subscribers\\StateEntities::__get()',
		'FastyBird\\Bridge\\DevicesModuleUiModule\\Subscribers\\StateEntities::__isset()',
		'FastyBird\\Bridge\\DevicesModuleUiModule\\Subscribers\\StateEntities::__set()',
		'FastyBird\\Bridge\\DevicesModuleUiModule\\Subscribers\\StateEntities::__unset()',
	],
	'changed' => [
		'FastyBird\\Bridge\\DevicesModuleUiModule\\Consumers\\SocketsBridge' => [
			'readonly' => true,
		],
		'FastyBird\\Bridge\\DevicesModuleUiModule\\DI\\DevicesModuleUiModuleExtension::NAME' => [
			'type' => 'string',
		],
		'FastyBird\\Bridge\\DevicesModuleUiModule\\DI\\DevicesModuleUiModuleExtension' => [
			'final' => true,
		],
		'FastyBird\\Bridge\\DevicesModuleUiModule\\Documents\\Widgets\\DataSources\\ChannelProperty' => [
			'final' => true,
		],
		'FastyBird\\Bridge\\DevicesModuleUiModule\\Documents\\Widgets\\DataSources\\ConnectorProperty' => [
			'final' => true,
		],
		'FastyBird\\Bridge\\DevicesModuleUiModule\\Documents\\Widgets\\DataSources\\DeviceProperty' => [
			'final' => true,
		],
		'FastyBird\\Bridge\\DevicesModuleUiModule\\Entities\\Widgets\\DataSources\\ChannelProperty::TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Bridge\\DevicesModuleUiModule\\Entities\\Widgets\\DataSources\\ConnectorProperty::TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Bridge\\DevicesModuleUiModule\\Entities\\Widgets\\DataSources\\DeviceProperty::TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Bridge\\DevicesModuleUiModule\\Exceptions\\InvalidArgument' => [
			'final' => true,
		],
		'FastyBird\\Bridge\\DevicesModuleUiModule\\Queries\\Configuration\\FindWidgetChannelPropertyDataSources' => [
			'final' => true,
		],
		'FastyBird\\Bridge\\DevicesModuleUiModule\\Queries\\Configuration\\FindWidgetConnectorPropertyDataSources' => [
			'final' => true,
		],
		'FastyBird\\Bridge\\DevicesModuleUiModule\\Queries\\Configuration\\FindWidgetDevicePropertyDataSources' => [
			'final' => true,
		],
		'FastyBird\\Bridge\\DevicesModuleUiModule\\Queries\\Entities\\FindWidgetChannelPropertyDataSources' => [
			'final' => true,
		],
		'FastyBird\\Bridge\\DevicesModuleUiModule\\Queries\\Entities\\FindWidgetConnectorPropertyDataSources' => [
			'final' => true,
		],
		'FastyBird\\Bridge\\DevicesModuleUiModule\\Queries\\Entities\\FindWidgetDevicePropertyDataSources' => [
			'final' => true,
		],
		'FastyBird\\Bridge\\DevicesModuleUiModule\\Schemas\\Widgets\\DataSources\\ChannelProperty::RELATIONSHIPS_PROPERTY' => [
			'type' => 'string',
		],
		'FastyBird\\Bridge\\DevicesModuleUiModule\\Schemas\\Widgets\\DataSources\\ChannelProperty::SCHEMA_TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Bridge\\DevicesModuleUiModule\\Schemas\\Widgets\\DataSources\\ConnectorProperty::RELATIONSHIPS_PROPERTY' => [
			'type' => 'string',
		],
		'FastyBird\\Bridge\\DevicesModuleUiModule\\Schemas\\Widgets\\DataSources\\ConnectorProperty::SCHEMA_TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Bridge\\DevicesModuleUiModule\\Schemas\\Widgets\\DataSources\\DeviceProperty::RELATIONSHIPS_PROPERTY' => [
			'type' => 'string',
		],
		'FastyBird\\Bridge\\DevicesModuleUiModule\\Schemas\\Widgets\\DataSources\\DeviceProperty::SCHEMA_TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Bridge\\DevicesModuleUiModule\\Schemas\\Widgets\\DataSources\\Property::RELATIONSHIPS_PROPERTY' => [
			'type' => 'string',
		],
		'FastyBird\\Bridge\\DevicesModuleUiModule\\Subscribers\\ActionCommand' => [
			'readonly' => true,
			'traits' => [],
		],
		'FastyBird\\Bridge\\DevicesModuleUiModule\\Subscribers\\DocumentsMapper' => [
			'readonly' => true,
			'traits' => [],
		],
		'FastyBird\\Bridge\\DevicesModuleUiModule\\Subscribers\\ModuleEntities' => [
			'readonly' => true,
			'traits' => [],
		],
		'FastyBird\\Bridge\\DevicesModuleUiModule\\Subscribers\\StateEntities' => [
			'readonly' => true,
			'traits' => [],
		],
	],
];
