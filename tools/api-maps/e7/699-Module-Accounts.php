<?php declare(strict_types = 1);

/**
 * #699 (E7.5, #462): the Accounts module's untyped class constants gain a native type. The change list
 * format is documented in tools/api-surface.php.
 *
 * Mechanical rules only: every item below is a constant whose `type` goes from null (untyped) to
 * the type of its value. Nothing is renamed, added or removed, and `#[\Override]` is not part of
 * the manifest. The base is the manifest of `main` @ f2e389c02 (--write from a checkout of it).
 */

return [
	'changed' => [
		'FastyBird\\Module\\Accounts\\Commands\\Accounts\\Create::NAME' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Accounts\\Commands\\Install::NAME' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Accounts\\Constants::DEFAULT_ROLES' => [
			'type' => 'array',
		],
		'FastyBird\\Module\\Accounts\\Constants::IDENTITY_PASSWORD_MINIMAL_LENGTH' => [
			'type' => 'int',
		],
		'FastyBird\\Module\\Accounts\\Constants::IDENTITY_UID_MAXIMAL_LENGTH' => [
			'type' => 'int',
		],
		'FastyBird\\Module\\Accounts\\Constants::MESSAGE_BUS_ACCOUNT_DOCUMENT_CREATED_ROUTING_KEY' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Accounts\\Constants::MESSAGE_BUS_ACCOUNT_DOCUMENT_DELETED_ROUTING_KEY' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Accounts\\Constants::MESSAGE_BUS_ACCOUNT_DOCUMENT_REPORTED_ROUTING_KEY' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Accounts\\Constants::MESSAGE_BUS_ACCOUNT_DOCUMENT_UPDATED_ROUTING_KEY' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Accounts\\Constants::MESSAGE_BUS_CREATED_ENTITIES_ROUTING_KEYS_MAPPING' => [
			'type' => 'array',
		],
		'FastyBird\\Module\\Accounts\\Constants::MESSAGE_BUS_DELETED_ENTITIES_ROUTING_KEYS_MAPPING' => [
			'type' => 'array',
		],
		'FastyBird\\Module\\Accounts\\Constants::MESSAGE_BUS_EMAIL_DOCUMENT_CREATED_ROUTING_KEY' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Accounts\\Constants::MESSAGE_BUS_EMAIL_DOCUMENT_DELETED_ROUTING_KEY' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Accounts\\Constants::MESSAGE_BUS_EMAIL_DOCUMENT_REPORTED_ROUTING_KEY' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Accounts\\Constants::MESSAGE_BUS_EMAIL_DOCUMENT_UPDATED_ROUTING_KEY' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Accounts\\Constants::MESSAGE_BUS_IDENTITY_DOCUMENT_CREATED_ROUTING_KEY' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Accounts\\Constants::MESSAGE_BUS_IDENTITY_DOCUMENT_DELETED_ROUTING_KEY' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Accounts\\Constants::MESSAGE_BUS_IDENTITY_DOCUMENT_REPORTED_ROUTING_KEY' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Accounts\\Constants::MESSAGE_BUS_IDENTITY_DOCUMENT_UPDATED_ROUTING_KEY' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Accounts\\Constants::MESSAGE_BUS_ROLE_DOCUMENT_CREATED_ROUTING_KEY' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Accounts\\Constants::MESSAGE_BUS_ROLE_DOCUMENT_DELETED_ROUTING_KEY' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Accounts\\Constants::MESSAGE_BUS_ROLE_DOCUMENT_REPORTED_ROUTING_KEY' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Accounts\\Constants::MESSAGE_BUS_ROLE_DOCUMENT_UPDATED_ROUTING_KEY' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Accounts\\Constants::MESSAGE_BUS_UPDATED_ENTITIES_ROUTING_KEYS_MAPPING' => [
			'type' => 'array',
		],
		'FastyBird\\Module\\Accounts\\Constants::NOT_ASSIGNABLE_ROLES' => [
			'type' => 'array',
		],
		'FastyBird\\Module\\Accounts\\Constants::ROUTE_NAME_ACCOUNT' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Accounts\\Constants::ROUTE_NAME_ACCOUNTS' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Accounts\\Constants::ROUTE_NAME_ACCOUNT_EMAIL' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Accounts\\Constants::ROUTE_NAME_ACCOUNT_EMAILS' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Accounts\\Constants::ROUTE_NAME_ACCOUNT_EMAIL_RELATIONSHIP' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Accounts\\Constants::ROUTE_NAME_ACCOUNT_IDENTITIES' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Accounts\\Constants::ROUTE_NAME_ACCOUNT_IDENTITY' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Accounts\\Constants::ROUTE_NAME_ACCOUNT_IDENTITY_RELATIONSHIP' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Accounts\\Constants::ROUTE_NAME_ACCOUNT_RELATIONSHIP' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Accounts\\Constants::ROUTE_NAME_ME' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Accounts\\Constants::ROUTE_NAME_ME_EMAIL' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Accounts\\Constants::ROUTE_NAME_ME_EMAILS' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Accounts\\Constants::ROUTE_NAME_ME_EMAIL_RELATIONSHIP' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Accounts\\Constants::ROUTE_NAME_ME_IDENTITIES' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Accounts\\Constants::ROUTE_NAME_ME_IDENTITY' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Accounts\\Constants::ROUTE_NAME_ME_IDENTITY_RELATIONSHIP' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Accounts\\Constants::ROUTE_NAME_ME_RELATIONSHIP' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Accounts\\Constants::ROUTE_NAME_ROLE' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Accounts\\Constants::ROUTE_NAME_ROLES' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Accounts\\Constants::ROUTE_NAME_ROLE_CHILDREN' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Accounts\\Constants::ROUTE_NAME_ROLE_RELATIONSHIP' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Accounts\\Constants::ROUTE_NAME_SESSION' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Accounts\\Constants::ROUTE_NAME_SESSION_RELATIONSHIP' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Accounts\\Constants::ROUTING_PREFIX' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Accounts\\Constants::SINGLE_ROLES' => [
			'type' => 'array',
		],
		'FastyBird\\Module\\Accounts\\DI\\AccountsExtension::NAME' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Accounts\\Entities\\Tokens\\AccessToken::TOKEN_EXPIRATION' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Accounts\\Entities\\Tokens\\RefreshToken::TOKEN_EXPIRATION' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Accounts\\Router\\ApiRoutes::RELATION_ENTITY' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Accounts\\Router\\ApiRoutes::URL_ACCOUNT_ID' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Accounts\\Router\\ApiRoutes::URL_ITEM_ID' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Accounts\\Schemas\\Accounts\\Account::RELATIONSHIPS_EMAILS' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Accounts\\Schemas\\Accounts\\Account::RELATIONSHIPS_IDENTITIES' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Accounts\\Schemas\\Accounts\\Account::RELATIONSHIPS_ROLES' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Accounts\\Schemas\\Accounts\\Account::SCHEMA_TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Accounts\\Schemas\\Emails\\Email::RELATIONSHIPS_ACCOUNT' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Accounts\\Schemas\\Emails\\Email::SCHEMA_TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Accounts\\Schemas\\Identities\\Identity::RELATIONSHIPS_ACCOUNT' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Accounts\\Schemas\\Identities\\Identity::SCHEMA_TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Accounts\\Schemas\\Roles\\Role::RELATIONSHIPS_CHILDREN' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Accounts\\Schemas\\Roles\\Role::RELATIONSHIPS_PARENT' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Accounts\\Schemas\\Roles\\Role::SCHEMA_TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Accounts\\Schemas\\Sessions\\Session::RELATIONSHIPS_ACCOUNT' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Accounts\\Schemas\\Sessions\\Session::SCHEMA_TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Module\\Accounts\\Security\\Authenticator::ACCOUNT_PROFILE_BLOCKED' => [
			'type' => 'int',
		],
		'FastyBird\\Module\\Accounts\\Security\\Authenticator::ACCOUNT_PROFILE_DELETED' => [
			'type' => 'int',
		],
		'FastyBird\\Module\\Accounts\\Security\\Authenticator::ACCOUNT_PROFILE_OTHER_ERROR' => [
			'type' => 'int',
		],
		'FastyBird\\Module\\Accounts\\Security\\Authenticator::IDENTITY_UID_NOT_FOUND' => [
			'type' => 'int',
		],
		'FastyBird\\Module\\Accounts\\Security\\Authenticator::INVALID_CREDENTIAL_FOR_UID' => [
			'type' => 'int',
		],
	],
];
