<?php declare(strict_types = 1);

/**
 * ApiRoutes.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:AccountsModule!
 * @subpackage     Router
 * @since          1.0.0
 *
 * @date           31.03.20
 */

namespace FastyBird\Module\Accounts\Router;

use FastyBird\Core\Constants as Metadata;
use FastyBird\Core\Middleware\SimpleAuth as SimpleAuthMiddleware;
use FastyBird\Core\Routing as SlimRouterRouting;
use FastyBird\Module\Accounts;
use FastyBird\Module\Accounts\Controllers;
use FastyBird\Module\Accounts\Middleware;

/**
 * Module router configuration
 *
 * @package        FastyBird:AccountsModule!
 * @subpackage     Router
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class ApiRoutes
{

	public const URL_ITEM_ID = 'id';

	public const URL_ACCOUNT_ID = 'account';

	public const RELATION_ENTITY = 'relationEntity';

	public function __construct(
		private readonly bool $usePrefix,
		private readonly Controllers\PublicV1 $publicV1Controller,
		private readonly Controllers\SessionV1 $sessionV1Controller,
		private readonly Controllers\AccountV1 $accountV1Controller,
		private readonly Controllers\AccountEmailsV1 $accountEmailsV1Controller,
		private readonly Controllers\AccountIdentitiesV1 $accountIdentitiesV1Controller,
		private readonly Controllers\AccountsV1 $accountsV1Controller,
		private readonly Controllers\EmailsV1 $emailsV1Controller,
		private readonly Controllers\IdentitiesV1 $identitiesV1Controller,
		private readonly Controllers\RolesV1 $rolesV1Controller,
		private readonly Controllers\RoleChildrenV1 $roleChildrenV1Controller,
		private readonly Middleware\Access $authAccessControlMiddleware,
		private readonly SimpleAuthMiddleware\Authorization $authorizationMiddleware,
		private readonly SimpleAuthMiddleware\User $userMiddleware,
	)
	{
	}

	public function registerRoutes(SlimRouterRouting\IRouter $router): void
	{
		$routes = $router->group('/' . Metadata\Constants::ROUTER_API_PREFIX, function (
			SlimRouterRouting\RouteCollector $group,
		): void {
			if ($this->usePrefix) {
				$group->group('/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX, function (
					SlimRouterRouting\RouteCollector $group,
				): void {
					$this->buildRoutes($group);
				});

			} else {
				$this->buildRoutes($group);
			}
		});

		$routes->addMiddleware($this->authorizationMiddleware);
		$routes->addMiddleware($this->userMiddleware);
		$routes->addMiddleware($this->authAccessControlMiddleware);
	}

	private function buildRoutes(
		SlimRouterRouting\IRouter|SlimRouterRouting\IRouteCollector $group,
	): SlimRouterRouting\IRouteGroup
	{
		return $group->group('/v1', function (SlimRouterRouting\RouteCollector $group): void {
			$group->post('/reset-identity', [$this->publicV1Controller, 'resetIdentity']);

			$group->post('/register', [$this->publicV1Controller, 'register']);

			/**
			 * SESSION
			 */
			$group->group('/session', function (SlimRouterRouting\RouteCollector $group): void {
				$route = $group->get('', [$this->sessionV1Controller, 'read']);
				$route->setName(Accounts\Constants::ROUTE_NAME_SESSION);

				$group->post('', [$this->sessionV1Controller, 'create']);

				$group->patch('', [$this->sessionV1Controller, 'update']);

				$group->delete('', [$this->sessionV1Controller, 'delete']);

				$route = $group->get('/relationships/{' . self::RELATION_ENTITY . '}', [
					$this->sessionV1Controller,
					'readRelationship',
				]);
				$route->setName(Accounts\Constants::ROUTE_NAME_SESSION_RELATIONSHIP);
			});

			/**
			 * PROFILE
			 */
			$group->group('/me', function (SlimRouterRouting\RouteCollector $group): void {
				$route = $group->get('', [$this->accountV1Controller, 'read']);
				$route->setName(Accounts\Constants::ROUTE_NAME_ME);

				$group->patch('', [$this->accountV1Controller, 'update']);

				$group->delete('', [$this->accountV1Controller, 'delete']);

				$route = $group->get('/relationships/{' . self::RELATION_ENTITY . '}', [
					$this->accountV1Controller,
					'readRelationship',
				]);
				$route->setName(Accounts\Constants::ROUTE_NAME_ME_RELATIONSHIP);

				/**
				 * PROFILE EMAILS
				 */
				$group->group('/emails', function (SlimRouterRouting\RouteCollector $group): void {
					$route = $group->get('', [$this->accountEmailsV1Controller, 'index']);
					$route->setName(Accounts\Constants::ROUTE_NAME_ME_EMAILS);

					$route = $group->get('/{' . self::URL_ITEM_ID . '}', [$this->accountEmailsV1Controller, 'read']);
					$route->setName(Accounts\Constants::ROUTE_NAME_ME_EMAIL);

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
					$route->setName(Accounts\Constants::ROUTE_NAME_ME_EMAIL_RELATIONSHIP);
				});

				/**
				 * PROFILE IDENTITIES
				 */
				$group->group('/identities', function (SlimRouterRouting\RouteCollector $group): void {
					$route = $group->get('', [$this->accountIdentitiesV1Controller, 'index']);
					$route->setName(Accounts\Constants::ROUTE_NAME_ME_IDENTITIES);

					$route = $group->get('/{' . self::URL_ITEM_ID . '}', [
						$this->accountIdentitiesV1Controller,
						'read',
					]);
					$route->setName(Accounts\Constants::ROUTE_NAME_ME_IDENTITY);

					$group->patch('/{' . self::URL_ITEM_ID . '}', [$this->accountIdentitiesV1Controller, 'update']);

					$route = $group->get(
						'/{' . self::URL_ITEM_ID . '}/relationships/{' . self::RELATION_ENTITY . '}',
						[
							$this->accountIdentitiesV1Controller,
							'readRelationship',
						],
					);
					$route->setName(Accounts\Constants::ROUTE_NAME_ME_IDENTITY_RELATIONSHIP);
				});
			});

			/**
			 * ACCOUNTS
			 */
			$group->group('/accounts', function (SlimRouterRouting\RouteCollector $group): void {
				$route = $group->get('', [$this->accountsV1Controller, 'index']);
				$route->setName(Accounts\Constants::ROUTE_NAME_ACCOUNTS);

				$route = $group->get('/{' . self::URL_ITEM_ID . '}', [$this->accountsV1Controller, 'read']);
				$route->setName(Accounts\Constants::ROUTE_NAME_ACCOUNT);

				$group->post('', [$this->accountsV1Controller, 'create']);

				$group->patch('/{' . self::URL_ITEM_ID . '}', [$this->accountsV1Controller, 'update']);

				$group->delete('/{' . self::URL_ITEM_ID . '}', [$this->accountsV1Controller, 'delete']);

				$route = $group->get('/{' . self::URL_ITEM_ID . '}/relationships/{' . self::RELATION_ENTITY . '}', [
					$this->accountsV1Controller,
					'readRelationship',
				]);
				$route->setName(Accounts\Constants::ROUTE_NAME_ACCOUNT_RELATIONSHIP);
			});

			$group->group(
				'/accounts/{' . self::URL_ACCOUNT_ID . '}',
				function (SlimRouterRouting\RouteCollector $group): void {
					/**
					 * ACCOUNT IDENTITIES
					 */
					$group->group('/identities', function (SlimRouterRouting\RouteCollector $group): void {
						$route = $group->get('', [$this->identitiesV1Controller, 'index']);
						$route->setName(Accounts\Constants::ROUTE_NAME_ACCOUNT_IDENTITIES);

						$route = $group->get('/{' . self::URL_ITEM_ID . '}', [$this->identitiesV1Controller, 'read']);
						$route->setName(Accounts\Constants::ROUTE_NAME_ACCOUNT_IDENTITY);

						$group->post('', [$this->identitiesV1Controller, 'create']);

						$group->patch('/{' . self::URL_ITEM_ID . '}', [$this->identitiesV1Controller, 'update']);

						$route = $group->get(
							'/{' . self::URL_ITEM_ID . '}/relationships/{' . self::RELATION_ENTITY . '}',
							[
								$this->identitiesV1Controller,
								'readRelationship',
							],
						);
						$route->setName(Accounts\Constants::ROUTE_NAME_ACCOUNT_IDENTITY_RELATIONSHIP);
					});

					/**
					 * ACCOUNT EMAILS
					 */
					$group->group('/emails', function (SlimRouterRouting\RouteCollector $group): void {
						$route = $group->get('', [$this->emailsV1Controller, 'index']);
						$route->setName(Accounts\Constants::ROUTE_NAME_ACCOUNT_EMAILS);

						$route = $group->get('/{' . self::URL_ITEM_ID . '}', [$this->emailsV1Controller, 'read']);
						$route->setName(Accounts\Constants::ROUTE_NAME_ACCOUNT_EMAIL);

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
						$route->setName(Accounts\Constants::ROUTE_NAME_ACCOUNT_EMAIL_RELATIONSHIP);
					});
				},
			);

			/**
			 * ACCESS ROLES
			 */
			$group->group('/roles', function (SlimRouterRouting\RouteCollector $group): void {
				$route = $group->get('', [$this->rolesV1Controller, 'index']);
				$route->setName(Accounts\Constants::ROUTE_NAME_ROLES);

				$route = $group->get('/{' . self::URL_ITEM_ID . '}', [$this->rolesV1Controller, 'read']);
				$route->setName(Accounts\Constants::ROUTE_NAME_ROLE);

				$group->patch('/{' . self::URL_ITEM_ID . '}', [$this->rolesV1Controller, 'update']);

				$route = $group->get('/{' . self::URL_ITEM_ID . '}/relationships/{' . self::RELATION_ENTITY . '}', [
					$this->rolesV1Controller,
					'readRelationship',
				]);
				$route->setName(Accounts\Constants::ROUTE_NAME_ROLE_RELATIONSHIP);

				/**
				 * CHILDREN
				 */
				$route = $group->get('/{' . self::URL_ITEM_ID . '}/children', [
					$this->roleChildrenV1Controller,
					'index',
				]);
				$route->setName(Accounts\Constants::ROUTE_NAME_ROLE_CHILDREN);
			});

			$group->group('/authenticate', static function (SlimRouterRouting\RouteCollector $group): void {
			});
		});
	}

}
