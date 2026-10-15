<?php declare(strict_types = 1);

namespace FastyBird\Module\Triggers\Types;

use FastyBird\Core\Exchange\Publisher;

/**
 * Exchange routing keys of the module's documents and actions
 *
 * The constant is the prefix the document keys are built from. It is not a key itself, so it is
 * not a case.
 */
enum RoutingKey: string
{

	public const string PREFIX = Publisher\MessagePublisher::ROUTING_KEY_PREFIX . '.module.document';

	// TRIGGERS
	case TRIGGER_DOCUMENT_REPORTED = self::PREFIX . '.reported.trigger';

	case TRIGGER_DOCUMENT_CREATED = self::PREFIX . '.created.trigger';

	case TRIGGER_DOCUMENT_UPDATED = self::PREFIX . '.updated.trigger';

	case TRIGGER_DOCUMENT_DELETED = self::PREFIX . '.deleted.trigger';

	// TRIGGERS CONTROLS
	case TRIGGER_CONTROL_DOCUMENT_REPORTED = self::PREFIX . '.reported.trigger.control';

	case TRIGGER_CONTROL_DOCUMENT_CREATED = self::PREFIX . '.created.trigger.control';

	case TRIGGER_CONTROL_DOCUMENT_UPDATED = self::PREFIX . '.updated.trigger.control';

	case TRIGGER_CONTROL_DOCUMENT_DELETED = self::PREFIX . '.deleted.trigger.control';

	// TRIGGERS ACTIONS
	case ACTION_DOCUMENT_REPORTED = self::PREFIX . '.reported.trigger.action';

	case ACTION_DOCUMENT_CREATED = self::PREFIX . '.created.trigger.action';

	case ACTION_DOCUMENT_UPDATED = self::PREFIX . '.updated.trigger.action';

	case ACTION_DOCUMENT_DELETED = self::PREFIX . '.deleted.trigger.action';

	// TRIGGERS NOTIFICATIONS
	case NOTIFICATION_DOCUMENT_REPORTED = self::PREFIX . '.reported.trigger.notification';

	case NOTIFICATION_DOCUMENT_CREATED = self::PREFIX . '.created.trigger.notification';

	case NOTIFICATION_DOCUMENT_UPDATED = self::PREFIX . '.updated.trigger.notification';

	case NOTIFICATION_DOCUMENT_DELETED = self::PREFIX . '.deleted.trigger.notification';

	// TRIGGERS CONDITIONS
	case CONDITION_DOCUMENT_REPORTED = self::PREFIX . '.reported.trigger.condition';

	case CONDITION_DOCUMENT_CREATED = self::PREFIX . '.created.trigger.condition';

	case CONDITION_DOCUMENT_UPDATED = self::PREFIX . '.updated.trigger.condition';

	case CONDITION_DOCUMENT_DELETED = self::PREFIX . '.deleted.trigger.condition';

	case TRIGGER_CONTROL_ACTION = Publisher\MessagePublisher::ROUTING_KEY_PREFIX . '.action.trigger.control';

}
