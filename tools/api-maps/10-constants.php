<?php declare(strict_types = 1);

/**
 * E5.10 (#642): dissolve FastyBird\Core\Constants into the types that own each constant (#460
 * §3.8, census T5). The change list format is documented in tools/api-surface.php.
 *
 * The 19 constants that stay in Core (rules C3 and C4) are renamed onto their owning type: each
 * keeps its visibility, its `string` type and its value, and only its address changes. The other
 * 44 leave Core's API with the class itself, which is removed:
 *   - the 33 C1 identities became the literals of the Values\Types\Sources enum cases, whose
 *     recorded backing values are therefore unchanged and appear nowhere below;
 *   - the 11 C2 constants moved to Accounts, Devices, Triggers, Ui, the three HomeKit bridges and
 *     RedisDb, none of which is a FastyBird\Core type the manifest records.
 */

return [
	'renamed' => [
		// C3 -- one Core capability: a typed constant on the owning type
		'FastyBird\\Core\\Constants::TOKEN_HEADER_NAME' => 'FastyBird\\Core\\Security\\Identity\\TokenReader::HEADER_NAME',
		'FastyBird\\Core\\Constants::TOKEN_HEADER_REGEXP' => 'FastyBird\\Core\\Security\\Identity\\TokenReader::HEADER_PATTERN',
		'FastyBird\\Core\\Constants::TOKEN_CLAIM_USER' => 'FastyBird\\Core\\Security\\Identity\\TokenBuilder::CLAIM_USER',
		'FastyBird\\Core\\Constants::TOKEN_CLAIM_ROLES' => 'FastyBird\\Core\\Security\\Identity\\TokenBuilder::CLAIM_ROLES',
		'FastyBird\\Core\\Constants::PERMISSIONS_DELIMITER' => 'FastyBird\\Core\\Security\\Access\\Checker::PERMISSIONS_DELIMITER',
		'FastyBird\\Core\\Constants::WS_HEADER_AUTHORIZATION' => 'FastyBird\\Core\\WebSockets\\Subscribers\\Client::HEADER_AUTHORIZATION',
		'FastyBird\\Core\\Constants::WS_HEADER_WS_KEY' => 'FastyBird\\Core\\WebSockets\\Subscribers\\Client::HEADER_WS_KEY',
		'FastyBird\\Core\\Constants::WS_HEADER_ORIGIN' => 'FastyBird\\Core\\WebSockets\\Subscribers\\Client::HEADER_ORIGIN',
		// C4 -- shared: the capability that defines its meaning
		'FastyBird\\Core\\Constants::ROUTER_API_PREFIX' => 'FastyBird\\Core\\Http\\Routing\\Router::API_PREFIX',
		'FastyBird\\Core\\Constants::VALUE_NOT_SET' => 'FastyBird\\Core\\Values\\Utilities\\Value::NOT_SET',
		'FastyBird\\Core\\Constants::VALUE_EQUATION_TRANSFORMER' => 'FastyBird\\Core\\Values\\Transformers\\EquationTransformer::PATTERN',
		'FastyBird\\Core\\Constants::MESSAGE_BUS_PREFIX_KEY' => 'FastyBird\\Core\\Exchange\\Publisher\\MessagePublisher::ROUTING_KEY_PREFIX',
		'FastyBird\\Core\\Constants::ACCESS_TOKEN_COOKIE' => 'FastyBird\\Core\\Security\\Identity\\TokenReader::COOKIE_NAME',
		'FastyBird\\Core\\Constants::ROLE_ANONYMOUS' => 'FastyBird\\Core\\Security\\Identity\\User::ROLE_ANONYMOUS',
		'FastyBird\\Core\\Constants::ROLE_VISITOR' => 'FastyBird\\Core\\Security\\Identity\\User::ROLE_VISITOR',
		'FastyBird\\Core\\Constants::ROLE_USER' => 'FastyBird\\Core\\Security\\Identity\\User::ROLE_USER',
		'FastyBird\\Core\\Constants::ROLE_MANAGER' => 'FastyBird\\Core\\Security\\Identity\\User::ROLE_MANAGER',
		'FastyBird\\Core\\Constants::ROLE_ADMINISTRATOR' => 'FastyBird\\Core\\Security\\Identity\\User::ROLE_ADMINISTRATOR',
		'FastyBird\\Core\\Constants::USER_ANONYMOUS' => 'FastyBird\\Core\\Security\\Identity\\User::ANONYMOUS_ID',
	],
	'removed' => [
		'FastyBird\\Core\\Constants',
	],
];
