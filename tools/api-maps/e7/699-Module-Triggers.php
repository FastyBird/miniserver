<?php declare(strict_types = 1);

/**
 * #699 (E7.5, #462): the Triggers module's untyped class constants gain a native type. The change list
 * format is documented in tools/api-surface.php.
 *
 * Mechanical rules only: every item below is a constant whose `type` goes from null (untyped) to
 * the type of its value. Nothing is renamed, added or removed, and `#[\Override]` is not part of
 * the manifest. The base is the manifest of `main` @ f2e389c02 (--write from a checkout of it).
 */

return [
	'changed' => [
		'FastyBird\\Module\\Triggers\\Commands\\Install::NAME' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Triggers\\Constants::MESSAGE_BUS_ACTION_DOCUMENT_CREATED_ROUTING_KEY' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Triggers\\Constants::MESSAGE_BUS_ACTION_DOCUMENT_DELETED_ROUTING_KEY' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Triggers\\Constants::MESSAGE_BUS_ACTION_DOCUMENT_REPORTED_ROUTING_KEY' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Triggers\\Constants::MESSAGE_BUS_ACTION_DOCUMENT_UPDATED_ROUTING_KEY' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Triggers\\Constants::MESSAGE_BUS_CONDITION_DOCUMENT_CREATED_ROUTING_KEY' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Triggers\\Constants::MESSAGE_BUS_CONDITION_DOCUMENT_DELETED_ROUTING_KEY' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Triggers\\Constants::MESSAGE_BUS_CONDITION_DOCUMENT_REPORTED_ROUTING_KEY' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Triggers\\Constants::MESSAGE_BUS_CONDITION_DOCUMENT_UPDATED_ROUTING_KEY' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Triggers\\Constants::MESSAGE_BUS_CREATED_ENTITIES_ROUTING_KEYS_MAPPING' => [
			'type' => 'array',
		],
		'FastyBird\\Module\\Triggers\\Constants::MESSAGE_BUS_DELETED_ENTITIES_ROUTING_KEYS_MAPPING' => [
			'type' => 'array',
		],
		'FastyBird\\Module\\Triggers\\Constants::MESSAGE_BUS_NOTIFICATION_DOCUMENT_CREATED_ROUTING_KEY' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Triggers\\Constants::MESSAGE_BUS_NOTIFICATION_DOCUMENT_DELETED_ROUTING_KEY' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Triggers\\Constants::MESSAGE_BUS_NOTIFICATION_DOCUMENT_REPORTED_ROUTING_KEY' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Triggers\\Constants::MESSAGE_BUS_NOTIFICATION_DOCUMENT_UPDATED_ROUTING_KEY' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Triggers\\Constants::MESSAGE_BUS_TRIGGER_CONTROL_ACTION_ROUTING_KEY' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Triggers\\Constants::MESSAGE_BUS_TRIGGER_CONTROL_DOCUMENT_CREATED_ROUTING_KEY' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Triggers\\Constants::MESSAGE_BUS_TRIGGER_CONTROL_DOCUMENT_DELETED_ROUTING_KEY' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Triggers\\Constants::MESSAGE_BUS_TRIGGER_CONTROL_DOCUMENT_REPORTED_ROUTING_KEY' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Triggers\\Constants::MESSAGE_BUS_TRIGGER_CONTROL_DOCUMENT_UPDATED_ROUTING_KEY' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Triggers\\Constants::MESSAGE_BUS_TRIGGER_DOCUMENT_CREATED_ROUTING_KEY' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Triggers\\Constants::MESSAGE_BUS_TRIGGER_DOCUMENT_DELETED_ROUTING_KEY' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Triggers\\Constants::MESSAGE_BUS_TRIGGER_DOCUMENT_REPORTED_ROUTING_KEY' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Triggers\\Constants::MESSAGE_BUS_TRIGGER_DOCUMENT_UPDATED_ROUTING_KEY' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Triggers\\Constants::MESSAGE_BUS_UPDATED_ENTITIES_ROUTING_KEYS_MAPPING' => [
			'type' => 'array',
		],
		'FastyBird\\Module\\Triggers\\Constants::ROUTE_NAME_TRIGGER' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Triggers\\Constants::ROUTE_NAME_TRIGGERS' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Triggers\\Constants::ROUTE_NAME_TRIGGER_ACTION' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Triggers\\Constants::ROUTE_NAME_TRIGGER_ACTIONS' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Triggers\\Constants::ROUTE_NAME_TRIGGER_ACTION_RELATIONSHIP' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Triggers\\Constants::ROUTE_NAME_TRIGGER_CONDITION' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Triggers\\Constants::ROUTE_NAME_TRIGGER_CONDITIONS' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Triggers\\Constants::ROUTE_NAME_TRIGGER_CONDITION_RELATIONSHIP' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Triggers\\Constants::ROUTE_NAME_TRIGGER_CONTROL' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Triggers\\Constants::ROUTE_NAME_TRIGGER_CONTROLS' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Triggers\\Constants::ROUTE_NAME_TRIGGER_CONTROL_RELATIONSHIP' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Triggers\\Constants::ROUTE_NAME_TRIGGER_NOTIFICATION' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Triggers\\Constants::ROUTE_NAME_TRIGGER_NOTIFICATIONS' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Triggers\\Constants::ROUTE_NAME_TRIGGER_NOTIFICATION_RELATIONSHIP' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Triggers\\Constants::ROUTE_NAME_TRIGGER_RELATIONSHIP' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Triggers\\Constants::ROUTING_PREFIX' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Triggers\\DI\\TriggersExtension::NAME' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Triggers\\DI\\TriggersExtension::TRIGGER_TYPE_TAG' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Triggers\\Entities\\Notifications\\Email::TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Triggers\\Entities\\Notifications\\Sms::TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Triggers\\Entities\\Triggers\\Automatic::TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Triggers\\Entities\\Triggers\\Manual::TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Triggers\\Router\\ApiRoutes::RELATION_ENTITY' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Triggers\\Router\\ApiRoutes::URL_ITEM_ID' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Triggers\\Router\\ApiRoutes::URL_TRIGGER_ID' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Triggers\\Schemas\\Actions\\Action::RELATIONSHIPS_TRIGGER' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Triggers\\Schemas\\Conditions\\Condition::RELATIONSHIPS_TRIGGER' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Triggers\\Schemas\\Notifications\\Email::SCHEMA_TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Triggers\\Schemas\\Notifications\\Notification::RELATIONSHIPS_TRIGGER' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Triggers\\Schemas\\Notifications\\Sms::SCHEMA_TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Triggers\\Schemas\\Triggers\\Automatic::RELATIONSHIPS_CONDITIONS' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Triggers\\Schemas\\Triggers\\Automatic::SCHEMA_TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Triggers\\Schemas\\Triggers\\Controls\\Control::RELATIONSHIPS_TRIGGER' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Triggers\\Schemas\\Triggers\\Controls\\Control::SCHEMA_TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Triggers\\Schemas\\Triggers\\Manual::RELATIONSHIPS_CONTROLS' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Triggers\\Schemas\\Triggers\\Manual::SCHEMA_TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Triggers\\Schemas\\Triggers\\Trigger::RELATIONSHIPS_ACTIONS' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Triggers\\Schemas\\Triggers\\Trigger::RELATIONSHIPS_NOTIFICATIONS' => [
			'type' => 'string',
		],
	],
];
