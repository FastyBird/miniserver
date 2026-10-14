<?php declare(strict_types = 1);

namespace FastyBird\Module\Accounts\Types;

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

	// Accounts
	case ACCOUNT_DOCUMENT_REPORTED = self::PREFIX . '.reported.account';

	case ACCOUNT_DOCUMENT_CREATED = self::PREFIX . '.created.account';

	case ACCOUNT_DOCUMENT_UPDATED = self::PREFIX . '.updated.account';

	case ACCOUNT_DOCUMENT_DELETED = self::PREFIX . '.deleted.account';

	// Emails
	case EMAIL_DOCUMENT_REPORTED = self::PREFIX . '.reported.email';

	case EMAIL_DOCUMENT_CREATED = self::PREFIX . '.created.email';

	case EMAIL_DOCUMENT_UPDATED = self::PREFIX . '.updated.email';

	case EMAIL_DOCUMENT_DELETED = self::PREFIX . '.deleted.email';

	// Identities
	case IDENTITY_DOCUMENT_REPORTED = self::PREFIX . '.reported.identity';

	case IDENTITY_DOCUMENT_CREATED = self::PREFIX . '.created.identity';

	case IDENTITY_DOCUMENT_UPDATED = self::PREFIX . '.updated.identity';

	case IDENTITY_DOCUMENT_DELETED = self::PREFIX . '.deleted.identity';

	// Roles
	case ROLE_DOCUMENT_REPORTED = self::PREFIX . '.reported.role';

	case ROLE_DOCUMENT_CREATED = self::PREFIX . '.created.role';

	case ROLE_DOCUMENT_UPDATED = self::PREFIX . '.updated.role';

	case ROLE_DOCUMENT_DELETED = self::PREFIX . '.deleted.role';

}
