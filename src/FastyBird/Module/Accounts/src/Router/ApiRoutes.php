<?php declare(strict_types = 1);

namespace FastyBird\Module\Accounts\Router;

use FastyBird\Core\Http\Routing;
use FastyBird\Core\Security\Middleware as SecurityMiddleware;
use FastyBird\Module\Accounts;
use FastyBird\Module\Accounts\Controllers;
use FastyBird\Module\Accounts\Middleware as AccountsMiddleware;

/**
 * Module router configuration
 */
final readonly class ApiRoutes
{

	public const string URL_ITEM_ID = 'id';

	public const string URL_ACCOUNT_ID = 'account';

	public const string RELATION_ENTITY = 'relationEntity';

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

	public function __construct(
		private bool $usePrefix,
		private Controllers\PublicV1 $publicV1Controller,
		private Controllers\SessionV1 $sessionV1Controller,
		private Controllers\AccountV1 $accountV1Controller,
		private Controllers\AccountEmailsV1 $accountEmailsV1Controller,
		private Controllers\AccountIdentitiesV1 $accountIdentitiesV1Controller,
		private Controllers\AccountsV1 $accountsV1Controller,
		private Controllers\EmailsV1 $emailsV1Controller,
		private Controllers\IdentitiesV1 $identitiesV1Controller,
		private Controllers\RolesV1 $rolesV1Controller,
		private Controllers\RoleChildrenV1 $roleChildrenV1Controller,
		private AccountsMiddleware\Access $authAccessControlMiddleware,
		private AccountsMiddleware\InteractiveOnly $interactiveOnlyMiddleware,
		private SecurityMiddleware\Authorization $authorizationMiddleware,
		private SecurityMiddleware\User $userMiddleware,
	)
	{
	}

	public function registerRoutes(Routing\Router $router): void
	{
		$routes = $router->group('/' . Routing\Router::API_PREFIX, function (
			Routing\RouteCollector $group,
		): void {
			if ($this->usePrefix) {
				$group->group('/' . Accounts\DI\AccountsExtension::MODULE_ACCOUNTS_PREFIX, function (
					Routing\RouteCollector $group,
				): void {
					$this->buildRoutes($group);
				});

			} else {
				$this->buildRoutes($group);
			}
		});

		$routes->addMiddleware($this->authorizationMiddleware);
		// Between User, which signs the request in, and the route's authorization: no route of this
		// module is open to an API key
		$routes->addMiddleware($this->interactiveOnlyMiddleware);
		$routes->addMiddleware($this->userMiddleware);
		$routes->addMiddleware($this->authAccessControlMiddleware);
	}

	private function buildRoutes(
		Routing\Router|Routing\RouteCollector $group,
	): Routing\RouteGroup
	{
		return $group->group('/v1', function (Routing\RouteCollector $group): void {
			$group->post('/reset-identity', [$this->publicV1Controller, 'resetIdentity']);

			$group->post('/register', [$this->publicV1Controller, 'register']);

			/**
			 * SESSION
			 */
			$group->group('/session', function (Routing\RouteCollector $group): void {
				$route = $group->get('', [$this->sessionV1Controller, 'read']);
				$route->setName(self::ROUTE_NAME_SESSION);

				$group->post('', [$this->sessionV1Controller, 'create']);

				$group->patch('', [$this->sessionV1Controller, 'update']);

				$group->delete('', [$this->sessionV1Controller, 'delete']);

				$route = $group->get('/relationships/{' . self::RELATION_ENTITY . '}', [
					$this->sessionV1Controller,
					'readRelationship',
				]);
				$route->setName(self::ROUTE_NAME_SESSION_RELATIONSHIP);
			});

			/**
			 * PROFILE
			 */
			$group->group('/me', function (Routing\RouteCollector $group): void {
				$route = $group->get('', [$this->accountV1Controller, 'read']);
				$route->setName(self::ROUTE_NAME_ME);

				$group->patch('', [$this->accountV1Controller, 'update']);

				$group->delete('', [$this->accountV1Controller, 'delete']);

				$route = $group->get('/relationships/{' . self::RELATION_ENTITY . '}', [
					$this->accountV1Controller,
					'readRelationship',
				]);
				$route->setName(self::ROUTE_NAME_ME_RELATIONSHIP);

				/**
				 * PROFILE EMAILS
				 */
				$group->group('/emails', function (Routing\RouteCollector $group): void {
					$route = $group->get('', [$this->accountEmailsV1Controller, 'index']);
					$route->setName(self::ROUTE_NAME_ME_EMAILS);

					$route = $group->get('/{' . self::URL_ITEM_ID . '}', [$this->accountEmailsV1Controller, 'read']);
					$route->setName(self::ROUTE_NAME_ME_EMAIL);

					$group->post('', [$this->accountEmailsV1Controller, 'create']);

					$group->patch('/{' . self::URL_ITEM_ID . '}', [$this->accountEmailsV1Controller, 'update']);

					$group->delete('/{' . self::URL_ITEM_ID . '}', [$this->accountEmailsV1Controller, 'delete']);

					$route = $group->get(
						'/{' . self::URL_ITEM_ID . '}/relationships/{' . self::RELATION_ENTITY . '}',
						[
							$this->accountEmailsV1Controller,
							'readRelationship',
						],
					);
					$route->setName(self::ROUTE_NAME_ME_EMAIL_RELATIONSHIP);
				});

				/**
				 * PROFILE IDENTITIES
				 */
				$group->group('/identities', function (Routing\RouteCollector $group): void {
					$route = $group->get('', [$this->accountIdentitiesV1Controller, 'index']);
					$route->setName(self::ROUTE_NAME_ME_IDENTITIES);

					$route = $group->get('/{' . self::URL_ITEM_ID . '}', [
						$this->accountIdentitiesV1Controller,
						'read',
					]);
					$route->setName(self::ROUTE_NAME_ME_IDENTITY);

					$group->patch('/{' . self::URL_ITEM_ID . '}', [$this->accountIdentitiesV1Controller, 'update']);

					$route = $group->get(
						'/{' . self::URL_ITEM_ID . '}/relationships/{' . self::RELATION_ENTITY . '}',
						[
							$this->accountIdentitiesV1Controller,
							'readRelationship',
						],
					);
					$route->setName(self::ROUTE_NAME_ME_IDENTITY_RELATIONSHIP);
				});
			});

			/**
			 * ACCOUNTS
			 */
			$group->group('/accounts', function (Routing\RouteCollector $group): void {
				$route = $group->get('', [$this->accountsV1Controller, 'index']);
				$route->setName(self::ROUTE_NAME_ACCOUNTS);

				$route = $group->get('/{' . self::URL_ITEM_ID . '}', [$this->accountsV1Controller, 'read']);
				$route->setName(self::ROUTE_NAME_ACCOUNT);

				$group->post('', [$this->accountsV1Controller, 'create']);

				$group->patch('/{' . self::URL_ITEM_ID . '}', [$this->accountsV1Controller, 'update']);

				$group->delete('/{' . self::URL_ITEM_ID . '}', [$this->accountsV1Controller, 'delete']);

				$route = $group->get('/{' . self::URL_ITEM_ID . '}/relationships/{' . self::RELATION_ENTITY . '}', [
					$this->accountsV1Controller,
					'readRelationship',
				]);
				$route->setName(self::ROUTE_NAME_ACCOUNT_RELATIONSHIP);
			});

			$group->group(
				'/accounts/{' . self::URL_ACCOUNT_ID . '}',
				function (Routing\RouteCollector $group): void {
					/**
					 * ACCOUNT IDENTITIES
					 */
					$group->group('/identities', function (Routing\RouteCollector $group): void {
						$route = $group->get('', [$this->identitiesV1Controller, 'index']);
						$route->setName(self::ROUTE_NAME_ACCOUNT_IDENTITIES);

						$route = $group->get('/{' . self::URL_ITEM_ID . '}', [$this->identitiesV1Controller, 'read']);
						$route->setName(self::ROUTE_NAME_ACCOUNT_IDENTITY);

						$group->post('', [$this->identitiesV1Controller, 'create']);

						$group->patch('/{' . self::URL_ITEM_ID . '}', [$this->identitiesV1Controller, 'update']);

						$route = $group->get(
							'/{' . self::URL_ITEM_ID . '}/relationships/{' . self::RELATION_ENTITY . '}',
							[
								$this->identitiesV1Controller,
								'readRelationship',
							],
						);
						$route->setName(self::ROUTE_NAME_ACCOUNT_IDENTITY_RELATIONSHIP);
					});

					/**
					 * ACCOUNT EMAILS
					 */
					$group->group('/emails', function (Routing\RouteCollector $group): void {
						$route = $group->get('', [$this->emailsV1Controller, 'index']);
						$route->setName(self::ROUTE_NAME_ACCOUNT_EMAILS);

						$route = $group->get('/{' . self::URL_ITEM_ID . '}', [$this->emailsV1Controller, 'read']);
						$route->setName(self::ROUTE_NAME_ACCOUNT_EMAIL);

						$group->post('', [$this->emailsV1Controller, 'create']);

						$group->patch('/{' . self::URL_ITEM_ID . '}', [$this->emailsV1Controller, 'update']);

						$group->delete('/{' . self::URL_ITEM_ID . '}', [$this->emailsV1Controller, 'delete']);

						$route = $group->get(
							'/{' . self::URL_ITEM_ID . '}/relationships/{' . self::RELATION_ENTITY . '}',
							[
								$this->emailsV1Controller,
								'readRelationship',
							],
						);
						$route->setName(self::ROUTE_NAME_ACCOUNT_EMAIL_RELATIONSHIP);
					});
				},
			);

			/**
			 * ACCESS ROLES
			 */
			$group->group('/roles', function (Routing\RouteCollector $group): void {
				$route = $group->get('', [$this->rolesV1Controller, 'index']);
				$route->setName(self::ROUTE_NAME_ROLES);

				$route = $group->get('/{' . self::URL_ITEM_ID . '}', [$this->rolesV1Controller, 'read']);
				$route->setName(self::ROUTE_NAME_ROLE);

				$group->patch('/{' . self::URL_ITEM_ID . '}', [$this->rolesV1Controller, 'update']);

				$route = $group->get('/{' . self::URL_ITEM_ID . '}/relationships/{' . self::RELATION_ENTITY . '}', [
					$this->rolesV1Controller,
					'readRelationship',
				]);
				$route->setName(self::ROUTE_NAME_ROLE_RELATIONSHIP);

				/**
				 * CHILDREN
				 */
				$route = $group->get('/{' . self::URL_ITEM_ID . '}/children', [
					$this->roleChildrenV1Controller,
					'index',
				]);
				$route->setName(self::ROUTE_NAME_ROLE_CHILDREN);
			});

			$group->group('/authenticate', static function (Routing\RouteCollector $group): void {
			});
		});
	}

}
