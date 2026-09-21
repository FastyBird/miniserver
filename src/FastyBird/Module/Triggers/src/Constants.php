<?php declare(strict_types = 1);

/**
 * Constants.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:TriggersModule!
 * @subpackage     common
 * @since          1.0.0
 *
 * @date           05.04.20
 */

namespace FastyBird\Module\Triggers;

use FastyBird\Core\Constants as Metadata;
use FastyBird\Module\Triggers\Entities as TriggersModuleEntities;

/**
 * Service constants
 *
 * @package        FastyBird:TriggersModule!
 * @subpackage     common
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Constants
{

	/**
	 * MODULE API ROUTING
	 */

	public const ROUTE_NAME_TRIGGERS = 'triggers';

	public const ROUTE_NAME_TRIGGER = 'trigger';

	public const ROUTE_NAME_TRIGGER_RELATIONSHIP = 'trigger.relationship';

	public const ROUTE_NAME_TRIGGER_ACTIONS = 'trigger.actions';

	public const ROUTE_NAME_TRIGGER_ACTION = 'trigger.action';

	public const ROUTE_NAME_TRIGGER_ACTION_RELATIONSHIP = 'trigger.action.relationship';

	public const ROUTE_NAME_TRIGGER_CONDITIONS = 'trigger.conditions';

	public const ROUTE_NAME_TRIGGER_CONDITION = 'trigger.condition';

	public const ROUTE_NAME_TRIGGER_CONDITION_RELATIONSHIP = 'trigger.condition.relationship';

	public const ROUTE_NAME_TRIGGER_NOTIFICATIONS = 'trigger.notifications';

	public const ROUTE_NAME_TRIGGER_NOTIFICATION = 'trigger.notification';

	public const ROUTE_NAME_TRIGGER_NOTIFICATION_RELATIONSHIP = 'trigger.v.relationship';

	public const ROUTE_NAME_TRIGGER_CONTROLS = 'trigger.controls';

	public const ROUTE_NAME_TRIGGER_CONTROL = 'trigger.control';

	public const ROUTE_NAME_TRIGGER_CONTROL_RELATIONSHIP = 'trigger.control.relationship';

	/**
	 * MODULE MESSAGE BUS
	 */

	public const ROUTING_PREFIX = Metadata\Constants::MESSAGE_BUS_PREFIX_KEY . '.module.document';

	// TRIGGERS
	public const MESSAGE_BUS_TRIGGER_DOCUMENT_REPORTED_ROUTING_KEY = self::ROUTING_PREFIX . '.reported.trigger';

	public const MESSAGE_BUS_TRIGGER_DOCUMENT_CREATED_ROUTING_KEY = self::ROUTING_PREFIX . '.created.trigger';

	public const MESSAGE_BUS_TRIGGER_DOCUMENT_UPDATED_ROUTING_KEY = self::ROUTING_PREFIX . '.updated.trigger';

	public const MESSAGE_BUS_TRIGGER_DOCUMENT_DELETED_ROUTING_KEY = self::ROUTING_PREFIX . '.deleted.trigger';

	// TRIGGERS CONTROLS
	public const MESSAGE_BUS_TRIGGER_CONTROL_DOCUMENT_REPORTED_ROUTING_KEY = self::ROUTING_PREFIX . '.reported.trigger.control';

	public const MESSAGE_BUS_TRIGGER_CONTROL_DOCUMENT_CREATED_ROUTING_KEY = self::ROUTING_PREFIX . '.created.trigger.control';

	public const MESSAGE_BUS_TRIGGER_CONTROL_DOCUMENT_UPDATED_ROUTING_KEY = self::ROUTING_PREFIX . '.updated.trigger.control';

	public const MESSAGE_BUS_TRIGGER_CONTROL_DOCUMENT_DELETED_ROUTING_KEY = self::ROUTING_PREFIX . '.deleted.trigger.control';

	// TRIGGERS ACTIONS
	public const MESSAGE_BUS_ACTION_DOCUMENT_REPORTED_ROUTING_KEY = self::ROUTING_PREFIX . '.reported.trigger.action';

	public const MESSAGE_BUS_ACTION_DOCUMENT_CREATED_ROUTING_KEY = self::ROUTING_PREFIX . '.created.trigger.action';

	public const MESSAGE_BUS_ACTION_DOCUMENT_UPDATED_ROUTING_KEY = self::ROUTING_PREFIX . '.updated.trigger.action';

	public const MESSAGE_BUS_ACTION_DOCUMENT_DELETED_ROUTING_KEY = self::ROUTING_PREFIX . '.deleted.trigger.action';

	// TRIGGERS NOTIFICATIONS
	public const MESSAGE_BUS_NOTIFICATION_DOCUMENT_REPORTED_ROUTING_KEY = self::ROUTING_PREFIX . '.reported.trigger.notification';

	public const MESSAGE_BUS_NOTIFICATION_DOCUMENT_CREATED_ROUTING_KEY = self::ROUTING_PREFIX . '.created.trigger.notification';

	public const MESSAGE_BUS_NOTIFICATION_DOCUMENT_UPDATED_ROUTING_KEY = self::ROUTING_PREFIX . '.updated.trigger.notification';

	public const MESSAGE_BUS_NOTIFICATION_DOCUMENT_DELETED_ROUTING_KEY = self::ROUTING_PREFIX . '.deleted.trigger.notification';

	// TRIGGERS CONDITIONS
	public const MESSAGE_BUS_CONDITION_DOCUMENT_REPORTED_ROUTING_KEY = self::ROUTING_PREFIX . '.reported.trigger.condition';

	public const MESSAGE_BUS_CONDITION_DOCUMENT_CREATED_ROUTING_KEY = self::ROUTING_PREFIX . '.created.trigger.condition';

	public const MESSAGE_BUS_CONDITION_DOCUMENT_UPDATED_ROUTING_KEY = self::ROUTING_PREFIX . '.updated.trigger.condition';

	public const MESSAGE_BUS_CONDITION_DOCUMENT_DELETED_ROUTING_KEY = self::ROUTING_PREFIX . '.deleted.trigger.condition';

	public const MESSAGE_BUS_TRIGGER_CONTROL_ACTION_ROUTING_KEY = Metadata\Constants::MESSAGE_BUS_PREFIX_KEY . '.action.trigger.control';

	public const MESSAGE_BUS_CREATED_ENTITIES_ROUTING_KEYS_MAPPING = [
		TriggersModuleEntities\Triggers\Trigger::class => self::MESSAGE_BUS_TRIGGER_DOCUMENT_CREATED_ROUTING_KEY,
		TriggersModuleEntities\Actions\Action::class => self::MESSAGE_BUS_ACTION_DOCUMENT_CREATED_ROUTING_KEY,
		TriggersModuleEntities\Notifications\Notification::class => self::MESSAGE_BUS_NOTIFICATION_DOCUMENT_CREATED_ROUTING_KEY,
		TriggersModuleEntities\Conditions\Condition::class => self::MESSAGE_BUS_CONDITION_DOCUMENT_CREATED_ROUTING_KEY,
	];

	public const MESSAGE_BUS_UPDATED_ENTITIES_ROUTING_KEYS_MAPPING = [
		TriggersModuleEntities\Triggers\Trigger::class => self::MESSAGE_BUS_TRIGGER_DOCUMENT_UPDATED_ROUTING_KEY,
		TriggersModuleEntities\Actions\Action::class => self::MESSAGE_BUS_ACTION_DOCUMENT_UPDATED_ROUTING_KEY,
		TriggersModuleEntities\Notifications\Notification::class => self::MESSAGE_BUS_NOTIFICATION_DOCUMENT_UPDATED_ROUTING_KEY,
		TriggersModuleEntities\Conditions\Condition::class => self::MESSAGE_BUS_CONDITION_DOCUMENT_UPDATED_ROUTING_KEY,
	];

	public const MESSAGE_BUS_DELETED_ENTITIES_ROUTING_KEYS_MAPPING = [
		TriggersModuleEntities\Triggers\Trigger::class => self::MESSAGE_BUS_TRIGGER_DOCUMENT_DELETED_ROUTING_KEY,
		TriggersModuleEntities\Actions\Action::class => self::MESSAGE_BUS_ACTION_DOCUMENT_DELETED_ROUTING_KEY,
		TriggersModuleEntities\Notifications\Notification::class => self::MESSAGE_BUS_NOTIFICATION_DOCUMENT_DELETED_ROUTING_KEY,
		TriggersModuleEntities\Conditions\Condition::class => self::MESSAGE_BUS_CONDITION_DOCUMENT_DELETED_ROUTING_KEY,
	];

}
