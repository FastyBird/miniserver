<?php declare(strict_types = 1);

/**
 * E7.3 (#697), Automator/DevicesModule. The change list format is documented in tools/api-surface.php.
 *
 *   - 9 constants gain the type string (NAME, the four TYPE and the four SCHEMA_TYPE).
 *   - DI\DevicesModuleExtension, Exceptions\{InvalidArgument,UniqueActionConstraint,
 *     UniqueConditionConstraint} and Queries\Entities\{FindActions,FindConditions} become final
 *     (census T4). The Doctrine-mapped entities and Exceptions\InvalidState stay open.
 *   - Nette\SmartObject leaves the two Doctrine subscribers, and with it the six magic methods
 *     the trait declares. No other class of the package used it, directly or by inheritance.
 */

return [
	'removed' => [
		'FastyBird\\Automator\\DevicesModule\\Subscribers\\ActionEntity::__call()',
		'FastyBird\\Automator\\DevicesModule\\Subscribers\\ActionEntity::__callStatic()',
		'FastyBird\\Automator\\DevicesModule\\Subscribers\\ActionEntity::__get()',
		'FastyBird\\Automator\\DevicesModule\\Subscribers\\ActionEntity::__isset()',
		'FastyBird\\Automator\\DevicesModule\\Subscribers\\ActionEntity::__set()',
		'FastyBird\\Automator\\DevicesModule\\Subscribers\\ActionEntity::__unset()',
		'FastyBird\\Automator\\DevicesModule\\Subscribers\\ConditionEntity::__call()',
		'FastyBird\\Automator\\DevicesModule\\Subscribers\\ConditionEntity::__callStatic()',
		'FastyBird\\Automator\\DevicesModule\\Subscribers\\ConditionEntity::__get()',
		'FastyBird\\Automator\\DevicesModule\\Subscribers\\ConditionEntity::__isset()',
		'FastyBird\\Automator\\DevicesModule\\Subscribers\\ConditionEntity::__set()',
		'FastyBird\\Automator\\DevicesModule\\Subscribers\\ConditionEntity::__unset()',
	],
	'changed' => [
		'FastyBird\\Automator\\DevicesModule\\DI\\DevicesModuleExtension::NAME' => [
			'type' => 'string',
		],
		'FastyBird\\Automator\\DevicesModule\\DI\\DevicesModuleExtension' => [
			'final' => true,
		],
		'FastyBird\\Automator\\DevicesModule\\Entities\\Actions\\ChannelPropertyAction::TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Automator\\DevicesModule\\Entities\\Actions\\DevicePropertyAction::TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Automator\\DevicesModule\\Entities\\Conditions\\ChannelPropertyCondition::TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Automator\\DevicesModule\\Entities\\Conditions\\DevicePropertyCondition::TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Automator\\DevicesModule\\Exceptions\\InvalidArgument' => [
			'final' => true,
		],
		'FastyBird\\Automator\\DevicesModule\\Exceptions\\UniqueActionConstraint' => [
			'final' => true,
		],
		'FastyBird\\Automator\\DevicesModule\\Exceptions\\UniqueConditionConstraint' => [
			'final' => true,
		],
		'FastyBird\\Automator\\DevicesModule\\Queries\\Entities\\FindActions' => [
			'final' => true,
		],
		'FastyBird\\Automator\\DevicesModule\\Queries\\Entities\\FindConditions' => [
			'final' => true,
		],
		'FastyBird\\Automator\\DevicesModule\\Schemas\\Actions\\ChannelPropertyAction::SCHEMA_TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Automator\\DevicesModule\\Schemas\\Actions\\DevicePropertyAction::SCHEMA_TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Automator\\DevicesModule\\Schemas\\Conditions\\ChannelPropertyCondition::SCHEMA_TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Automator\\DevicesModule\\Schemas\\Conditions\\DevicePropertyCondition::SCHEMA_TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Automator\\DevicesModule\\Subscribers\\ActionEntity' => [
			'traits' => [],
		],
		'FastyBird\\Automator\\DevicesModule\\Subscribers\\ConditionEntity' => [
			'traits' => [],
		],
	],
];
