<?php declare(strict_types = 1);

/**
 * E7.2 (#696), Plugin/RabbitMq. The change list format is documented in tools/api-surface.php.
 *
 * public constants gain a type (6), final classes, readonly classes and Nette\SmartObject removed from 4 classes.
 * Channels\Channel stays open (mocked by its own class name) and Exceptions\InvalidState stays open
 * (UnprocessableMessage extends it).
 */

return [
	'removed' => [
		'FastyBird\\Plugin\\RabbitMq\\Channels\\Channel::__call()',
		'FastyBird\\Plugin\\RabbitMq\\Channels\\Channel::__callStatic()',
		'FastyBird\\Plugin\\RabbitMq\\Channels\\Channel::__get()',
		'FastyBird\\Plugin\\RabbitMq\\Channels\\Channel::__isset()',
		'FastyBird\\Plugin\\RabbitMq\\Channels\\Channel::__set()',
		'FastyBird\\Plugin\\RabbitMq\\Channels\\Channel::__unset()',
		'FastyBird\\Plugin\\RabbitMq\\Connections\\Connection::__call()',
		'FastyBird\\Plugin\\RabbitMq\\Connections\\Connection::__callStatic()',
		'FastyBird\\Plugin\\RabbitMq\\Connections\\Connection::__get()',
		'FastyBird\\Plugin\\RabbitMq\\Connections\\Connection::__isset()',
		'FastyBird\\Plugin\\RabbitMq\\Connections\\Connection::__set()',
		'FastyBird\\Plugin\\RabbitMq\\Connections\\Connection::__unset()',
		'FastyBird\\Plugin\\RabbitMq\\Publishers\\Publisher::__call()',
		'FastyBird\\Plugin\\RabbitMq\\Publishers\\Publisher::__callStatic()',
		'FastyBird\\Plugin\\RabbitMq\\Publishers\\Publisher::__get()',
		'FastyBird\\Plugin\\RabbitMq\\Publishers\\Publisher::__isset()',
		'FastyBird\\Plugin\\RabbitMq\\Publishers\\Publisher::__set()',
		'FastyBird\\Plugin\\RabbitMq\\Publishers\\Publisher::__unset()',
		'FastyBird\\Plugin\\RabbitMq\\Utilities\\IdentifierGenerator::__call()',
		'FastyBird\\Plugin\\RabbitMq\\Utilities\\IdentifierGenerator::__callStatic()',
		'FastyBird\\Plugin\\RabbitMq\\Utilities\\IdentifierGenerator::__get()',
		'FastyBird\\Plugin\\RabbitMq\\Utilities\\IdentifierGenerator::__isset()',
		'FastyBird\\Plugin\\RabbitMq\\Utilities\\IdentifierGenerator::__set()',
		'FastyBird\\Plugin\\RabbitMq\\Utilities\\IdentifierGenerator::__unset()',
	],
	'changed' => [
		'FastyBird\\Plugin\\RabbitMq\\Channels\\Channel' => [
			'traits' => [],
		],
		'FastyBird\\Plugin\\RabbitMq\\Channels\\Factory' => [
			'readonly' => true,
		],
		'FastyBird\\Plugin\\RabbitMq\\Connections\\Connection' => [
			'readonly' => true,
			'traits' => [],
		],
		'FastyBird\\Plugin\\RabbitMq\\DI\\RabbitMqExtension::EXCHANGE_NAME' => [
			'type' => 'string',
		],
		'FastyBird\\Plugin\\RabbitMq\\DI\\RabbitMqExtension::NAME' => [
			'type' => 'string',
		],
		'FastyBird\\Plugin\\RabbitMq\\DI\\RabbitMqExtension' => [
			'final' => true,
		],
		'FastyBird\\Plugin\\RabbitMq\\Events\\AfterMessageHandled' => [
			'final' => true,
		],
		'FastyBird\\Plugin\\RabbitMq\\Events\\BeforeMessageHandled' => [
			'final' => true,
		],
		'FastyBird\\Plugin\\RabbitMq\\Events\\ChannelCreated' => [
			'final' => true,
		],
		'FastyBird\\Plugin\\RabbitMq\\Events\\MessageConsumed' => [
			'final' => true,
		],
		'FastyBird\\Plugin\\RabbitMq\\Events\\MessageReceived' => [
			'final' => true,
		],
		'FastyBird\\Plugin\\RabbitMq\\Exceptions\\InvalidArgument' => [
			'final' => true,
		],
		'FastyBird\\Plugin\\RabbitMq\\Exceptions\\UnprocessableMessage' => [
			'final' => true,
		],
		'FastyBird\\Plugin\\RabbitMq\\Handlers\\Message::MESSAGE_ACK' => [
			'type' => 'int',
		],
		'FastyBird\\Plugin\\RabbitMq\\Handlers\\Message::MESSAGE_NACK' => [
			'type' => 'int',
		],
		'FastyBird\\Plugin\\RabbitMq\\Handlers\\Message::MESSAGE_REJECT' => [
			'type' => 'int',
		],
		'FastyBird\\Plugin\\RabbitMq\\Handlers\\Message::MESSAGE_REJECT_AND_TERMINATE' => [
			'type' => 'int',
		],
		'FastyBird\\Plugin\\RabbitMq\\Handlers\\Message' => [
			'readonly' => true,
		],
		'FastyBird\\Plugin\\RabbitMq\\Publishers\\Publisher' => [
			'traits' => [],
		],
		'FastyBird\\Plugin\\RabbitMq\\Subscribers\\Channel' => [
			'final' => true,
		],
		'FastyBird\\Plugin\\RabbitMq\\Utilities\\IdentifierGenerator' => [
			'traits' => [],
		],
	],
];
