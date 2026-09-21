<?php declare(strict_types = 1);

/**
 * Constants.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:AccountsModule!
 * @subpackage     common
 * @since          1.0.0
 *
 * @date           12.06.20
 */

namespace FastyBird\Module\Accounts;

use FastyBird\Core\Constants as Metadata;
use FastyBird\Core\Constants as SimpleAuth;

/**
 * Module constants
 *
 * @package        FastyBird:AccountsModule!
 * @subpackage     common
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Constants
{

	/**
	 * MODULE API ROUTING
	 */

	public const ROUTE_NAME_ME = 'me';

	public const ROUTE_NAME_ME_RELATIONSHIP = 'me.relationship';

	public const ROUTE_NAME_ME_EMAILS = 'me.emails';

	public const ROUTE_NAME_ME_EMAIL = 'me.email';

	public const ROUTE_NAME_ME_EMAIL_RELATIONSHIP = 'me.email.relationship';

	public const ROUTE_NAME_ME_IDENTITIES = 'me.identities';

	public const ROUTE_NAME_ME_IDENTITY = 'me.identity';

	public const ROUTE_NAME_ME_IDENTITY_RELATIONSHIP = 'me.identity.relationship';

	public const ROUTE_NAME_ACCOUNTS = 'accounts';

	public const ROUTE_NAME_ACCOUNT = 'account';

	public const ROUTE_NAME_ACCOUNT_RELATIONSHIP = 'account.relationship';

	public const ROUTE_NAME_ACCOUNT_EMAILS = 'account.emails';

	public const ROUTE_NAME_ACCOUNT_EMAIL = 'account.email';

	public const ROUTE_NAME_ACCOUNT_EMAIL_RELATIONSHIP = 'account.email.relationship';

	public const ROUTE_NAME_ACCOUNT_IDENTITIES = 'account.identities';

	public const ROUTE_NAME_ACCOUNT_IDENTITY = 'account.identity';

	public const ROUTE_NAME_ACCOUNT_IDENTITY_RELATIONSHIP = 'account.identity.relationship';

	public const ROUTE_NAME_SESSION = 'session';

	public const ROUTE_NAME_SESSION_RELATIONSHIP = 'session.relationship';

	public const ROUTE_NAME_ROLE = 'role';

	public const ROUTE_NAME_ROLES = 'roles';

	public const ROUTE_NAME_ROLE_RELATIONSHIP = 'role.relationship';

	public const ROUTE_NAME_ROLE_CHILDREN = 'role.children';

	/**
	 * ACCOUNTS DEFAULT ROLES
	 */

	public const DEFAULT_ROLES = [
		SimpleAuth\Constants::ROLE_USER,
	];

	public const SINGLE_ROLES = [
		SimpleAuth\Constants::ROLE_ADMINISTRATOR,
		SimpleAuth\Constants::ROLE_USER,
	];

	public const NOT_ASSIGNABLE_ROLES = [
		SimpleAuth\Constants::ROLE_VISITOR,
		SimpleAuth\Constants::ROLE_ANONYMOUS,
	];

	/**
	 * ACCOUNTS IDENTITIES
	 */

	public const IDENTITY_UID_MAXIMAL_LENGTH = 50;

	public const IDENTITY_PASSWORD_MINIMAL_LENGTH = 8;

	/**
	 * MODULE MESSAGE BUS
	 */

	public const ROUTING_PREFIX = Metadata\Constants::MESSAGE_BUS_PREFIX_KEY . '.module.document';

	// Accounts
	public const MESSAGE_BUS_ACCOUNT_DOCUMENT_REPORTED_ROUTING_KEY = self::ROUTING_PREFIX . '.reported.account';

	public const MESSAGE_BUS_ACCOUNT_DOCUMENT_CREATED_ROUTING_KEY = self::ROUTING_PREFIX . '.created.account';

	public const MESSAGE_BUS_ACCOUNT_DOCUMENT_UPDATED_ROUTING_KEY = self::ROUTING_PREFIX . '.updated.account';

	public const MESSAGE_BUS_ACCOUNT_DOCUMENT_DELETED_ROUTING_KEY = self::ROUTING_PREFIX . '.deleted.account';

	// Emails
	public const MESSAGE_BUS_EMAIL_DOCUMENT_REPORTED_ROUTING_KEY = self::ROUTING_PREFIX . '.reported.email';

	public const MESSAGE_BUS_EMAIL_DOCUMENT_CREATED_ROUTING_KEY = self::ROUTING_PREFIX . '.created.email';

	public const MESSAGE_BUS_EMAIL_DOCUMENT_UPDATED_ROUTING_KEY = self::ROUTING_PREFIX . '.updated.email';

	public const MESSAGE_BUS_EMAIL_DOCUMENT_DELETED_ROUTING_KEY = self::ROUTING_PREFIX . '.deleted.email';

	// Identities
	public const MESSAGE_BUS_IDENTITY_DOCUMENT_REPORTED_ROUTING_KEY = self::ROUTING_PREFIX . '.reported.identity';

	public const MESSAGE_BUS_IDENTITY_DOCUMENT_CREATED_ROUTING_KEY = self::ROUTING_PREFIX . '.created.identity';

	public const MESSAGE_BUS_IDENTITY_DOCUMENT_UPDATED_ROUTING_KEY = self::ROUTING_PREFIX . '.updated.identity';

	public const MESSAGE_BUS_IDENTITY_DOCUMENT_DELETED_ROUTING_KEY = self::ROUTING_PREFIX . '.deleted.identity';

	// Roles
	public const MESSAGE_BUS_ROLE_DOCUMENT_REPORTED_ROUTING_KEY = self::ROUTING_PREFIX . '.reported.role';

	public const MESSAGE_BUS_ROLE_DOCUMENT_CREATED_ROUTING_KEY = self::ROUTING_PREFIX . '.created.role';

	public const MESSAGE_BUS_ROLE_DOCUMENT_UPDATED_ROUTING_KEY = self::ROUTING_PREFIX . '.updated.role';

	public const MESSAGE_BUS_ROLE_DOCUMENT_DELETED_ROUTING_KEY = self::ROUTING_PREFIX . '.deleted.role';

	public const MESSAGE_BUS_CREATED_ENTITIES_ROUTING_KEYS_MAPPING = [
		Entities\Accounts\Account::class => self::MESSAGE_BUS_ACCOUNT_DOCUMENT_CREATED_ROUTING_KEY,
		Entities\Emails\Email::class => self::MESSAGE_BUS_EMAIL_DOCUMENT_CREATED_ROUTING_KEY,
		Entities\Identities\Identity::class => self::MESSAGE_BUS_IDENTITY_DOCUMENT_CREATED_ROUTING_KEY,
		Entities\Roles\Role::class => self::MESSAGE_BUS_ROLE_DOCUMENT_CREATED_ROUTING_KEY,
	];

	public const MESSAGE_BUS_UPDATED_ENTITIES_ROUTING_KEYS_MAPPING = [
		Entities\Accounts\Account::class => self::MESSAGE_BUS_ACCOUNT_DOCUMENT_UPDATED_ROUTING_KEY,
		Entities\Emails\Email::class => self::MESSAGE_BUS_EMAIL_DOCUMENT_UPDATED_ROUTING_KEY,
		Entities\Identities\Identity::class => self::MESSAGE_BUS_IDENTITY_DOCUMENT_UPDATED_ROUTING_KEY,
		Entities\Roles\Role::class => self::MESSAGE_BUS_ROLE_DOCUMENT_UPDATED_ROUTING_KEY,
	];

	public const MESSAGE_BUS_DELETED_ENTITIES_ROUTING_KEYS_MAPPING = [
		Entities\Accounts\Account::class => self::MESSAGE_BUS_ACCOUNT_DOCUMENT_DELETED_ROUTING_KEY,
		Entities\Emails\Email::class => self::MESSAGE_BUS_EMAIL_DOCUMENT_DELETED_ROUTING_KEY,
		Entities\Identities\Identity::class => self::MESSAGE_BUS_IDENTITY_DOCUMENT_DELETED_ROUTING_KEY,
		Entities\Roles\Role::class => self::MESSAGE_BUS_ROLE_DOCUMENT_DELETED_ROUTING_KEY,
	];

}
