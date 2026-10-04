<?php declare(strict_types = 1);

/**
 * E5.3a (#652): the dead Nette\Security\User API, deleted as escalation #650 resolved it (option D
 * plus the fail-closed guard), which completes census T2 row 12, X10 and T6 pair 35
 * (docs/superpowers/plans/2026-10-04-core-e5-census.md). The change list format is documented in
 * tools/api-surface.php; this list applies after 03-dead-code.php.
 *
 * nette/security is not installed (census D19), so Nette\Security\User never exists at runtime.
 * Every Core member typed against it is removed, and the injectPrimary() parameter $user with
 * them: it was the last parameter, so no other parameter changes position.
 *
 * Not in this list, because the manifest never recorded them:
 * - the WebSockets/Compat/User.php shim: it declares Nette\Security\User, outside
 *   FastyBird\Core\, and the autoloader never loads it;
 * - Controller::$user and Client::$user: private properties are not API;
 * - Controller::checkRequirements(): its signature and its `@throws` (ForbiddenRequest,
 *   InvalidState) are unchanged. `@User(loggedIn)` still ends in CoreExceptions\InvalidState, now
 *   thrown by checkRequirements() itself instead of by getUser().
 *
 * Declared DI changes (tools/di-snapshot.php, all 47 containers, base main @ 496402ef3): none. #652
 * allowed one, the `injectPrimary` setup arguments of the tagged WebSocket controller services
 * (Devices' and Ui's ExchangeV1), but the base never passed `$user`: no service of a class that
 * does not exist can be autowired, so Nette DI already dropped the trailing optional argument, and
 * both setups record the same three arguments (controllers.factory, routing.router,
 * routing.generator) before and after. The snapshot diff is "47 identical, 0 differ".
 */

return [
	'removed' => [
		'FastyBird\\Core\\WebSockets\\Controllers\\Controller::getUser()',
		'FastyBird\\Core\\WebSockets\\Entities\\Client::getUser()',
		'FastyBird\\Core\\WebSockets\\Entities\\Client::setUser()',
		'FastyBird\\Core\\WebSockets\\Entities\\ConnectedClient::getUser()',
		'FastyBird\\Core\\WebSockets\\Entities\\ConnectedClient::setUser()',
	],
	'changed' => [
		'FastyBird\\Core\\WebSockets\\Controllers\\Controller::injectPrimary()' => [
			'-parameters.$user' => null,
		],
	],
];
