<?php declare(strict_types = 1);

namespace FastyBird\Module\Accounts;

use FastyBird\Core\Exchange\Publisher;
use FastyBird\Core\Security\Identity;

/**
 * Module constants
 */
final class Constants
{

	/**
	 * MODULE API ROUTING
	 */

	public const string MODULE_ACCOUNTS_PREFIX = 'accounts-module';

	public const string ROUTE_NAME_ME = 'me';

	public const string ROUTE_NAME_ME_RELATIONSHIP = 'me.relationship';

	public const string ROUTE_NAME_ME_EMAILS = 'me.emails';

	public const string ROUTE_NAME_ME_EMAIL = 'me.email';

	public const string ROUTE_NAME_ME_EMAIL_RELATIONSHIP = 'me.email.relationship';

	public const string ROUTE_NAME_ME_IDENTITIES = 'me.identities';

	public const string ROUTE_NAME_ME_IDENTITY = 'me.identity';

	public const string ROUTE_NAME_ME_IDENTITY_RELATIONSHIP = 'me.identity.relationship';

	public const string ROUTE_NAME_ACCOUNTS = 'accounts';

	public const string ROUTE_NAME_ACCOUNT = 'account';

	public const string ROUTE_NAME_ACCOUNT_RELATIONSHIP = 'account.relationship';

	public const string ROUTE_NAME_ACCOUNT_EMAILS = 'account.emails';

	public const string ROUTE_NAME_ACCOUNT_EMAIL = 'account.email';

	public const string ROUTE_NAME_ACCOUNT_EMAIL_RELATIONSHIP = 'account.email.relationship';

	public const string ROUTE_NAME_ACCOUNT_IDENTITIES = 'account.identities';

	public const string ROUTE_NAME_ACCOUNT_IDENTITY = 'account.identity';

	public const string ROUTE_NAME_ACCOUNT_IDENTITY_RELATIONSHIP = 'account.identity.relationship';

	public const string ROUTE_NAME_SESSION = 'session';

	public const string ROUTE_NAME_SESSION_RELATIONSHIP = 'session.relationship';

	public const string ROUTE_NAME_ROLE = 'role';

	public const string ROUTE_NAME_ROLES = 'roles';

	public const string ROUTE_NAME_ROLE_RELATIONSHIP = 'role.relationship';

	public const string ROUTE_NAME_ROLE_CHILDREN = 'role.children';

	/**
	 * ACCOUNTS DEFAULT ROLES
	 */

	public const array DEFAULT_ROLES = [
		Identity\User::ROLE_USER,
	];

	public const array SINGLE_ROLES = [
		Identity\User::ROLE_ADMINISTRATOR,
		Identity\User::ROLE_USER,
	];

	public const array NOT_ASSIGNABLE_ROLES = [
		Identity\User::ROLE_VISITOR,
		Identity\User::ROLE_ANONYMOUS,
	];

	/**
	 * ACCOUNTS IDENTITIES
	 */

	public const int IDENTITY_UID_MAXIMAL_LENGTH = 50;

	public const int IDENTITY_PASSWORD_MINIMAL_LENGTH = 8;

	/**
	 * MODULE MESSAGE BUS
	 */

	public const string ROUTING_PREFIX = Publisher\MessagePublisher::ROUTING_KEY_PREFIX . '.module.document';

	// Accounts
	public const string MESSAGE_BUS_ACCOUNT_DOCUMENT_REPORTED_ROUTING_KEY = self::ROUTING_PREFIX . '.reported.account';

	public const string MESSAGE_BUS_ACCOUNT_DOCUMENT_CREATED_ROUTING_KEY = self::ROUTING_PREFIX . '.created.account';

	public const string MESSAGE_BUS_ACCOUNT_DOCUMENT_UPDATED_ROUTING_KEY = self::ROUTING_PREFIX . '.updated.account';

	public const string MESSAGE_BUS_ACCOUNT_DOCUMENT_DELETED_ROUTING_KEY = self::ROUTING_PREFIX . '.deleted.account';

	// Emails
	public const string MESSAGE_BUS_EMAIL_DOCUMENT_REPORTED_ROUTING_KEY = self::ROUTING_PREFIX . '.reported.email';

	public const string MESSAGE_BUS_EMAIL_DOCUMENT_CREATED_ROUTING_KEY = self::ROUTING_PREFIX . '.created.email';

	public const string MESSAGE_BUS_EMAIL_DOCUMENT_UPDATED_ROUTING_KEY = self::ROUTING_PREFIX . '.updated.email';

	public const string MESSAGE_BUS_EMAIL_DOCUMENT_DELETED_ROUTING_KEY = self::ROUTING_PREFIX . '.deleted.email';

	// Identities
	public const string MESSAGE_BUS_IDENTITY_DOCUMENT_REPORTED_ROUTING_KEY = self::ROUTING_PREFIX . '.reported.identity';

	public const string MESSAGE_BUS_IDENTITY_DOCUMENT_CREATED_ROUTING_KEY = self::ROUTING_PREFIX . '.created.identity';

	public const string MESSAGE_BUS_IDENTITY_DOCUMENT_UPDATED_ROUTING_KEY = self::ROUTING_PREFIX . '.updated.identity';

	public const string MESSAGE_BUS_IDENTITY_DOCUMENT_DELETED_ROUTING_KEY = self::ROUTING_PREFIX . '.deleted.identity';

	// Roles
	public const string MESSAGE_BUS_ROLE_DOCUMENT_REPORTED_ROUTING_KEY = self::ROUTING_PREFIX . '.reported.role';

	public const string MESSAGE_BUS_ROLE_DOCUMENT_CREATED_ROUTING_KEY = self::ROUTING_PREFIX . '.created.role';

	public const string MESSAGE_BUS_ROLE_DOCUMENT_UPDATED_ROUTING_KEY = self::ROUTING_PREFIX . '.updated.role';

	public const string MESSAGE_BUS_ROLE_DOCUMENT_DELETED_ROUTING_KEY = self::ROUTING_PREFIX . '.deleted.role';

	public const array MESSAGE_BUS_CREATED_ENTITIES_ROUTING_KEYS_MAPPING = [
		Entities\Accounts\Account::class => self::MESSAGE_BUS_ACCOUNT_DOCUMENT_CREATED_ROUTING_KEY,
		Entities\Emails\Email::class => self::MESSAGE_BUS_EMAIL_DOCUMENT_CREATED_ROUTING_KEY,
		Entities\Identities\Identity::class => self::MESSAGE_BUS_IDENTITY_DOCUMENT_CREATED_ROUTING_KEY,
		Entities\Roles\Role::class => self::MESSAGE_BUS_ROLE_DOCUMENT_CREATED_ROUTING_KEY,
	];

	public const array MESSAGE_BUS_UPDATED_ENTITIES_ROUTING_KEYS_MAPPING = [
		Entities\Accounts\Account::class => self::MESSAGE_BUS_ACCOUNT_DOCUMENT_UPDATED_ROUTING_KEY,
		Entities\Emails\Email::class => self::MESSAGE_BUS_EMAIL_DOCUMENT_UPDATED_ROUTING_KEY,
		Entities\Identities\Identity::class => self::MESSAGE_BUS_IDENTITY_DOCUMENT_UPDATED_ROUTING_KEY,
		Entities\Roles\Role::class => self::MESSAGE_BUS_ROLE_DOCUMENT_UPDATED_ROUTING_KEY,
	];

	public const array MESSAGE_BUS_DELETED_ENTITIES_ROUTING_KEYS_MAPPING = [
		Entities\Accounts\Account::class => self::MESSAGE_BUS_ACCOUNT_DOCUMENT_DELETED_ROUTING_KEY,
		Entities\Emails\Email::class => self::MESSAGE_BUS_EMAIL_DOCUMENT_DELETED_ROUTING_KEY,
		Entities\Identities\Identity::class => self::MESSAGE_BUS_IDENTITY_DOCUMENT_DELETED_ROUTING_KEY,
		Entities\Roles\Role::class => self::MESSAGE_BUS_ROLE_DOCUMENT_DELETED_ROUTING_KEY,
	];

}
