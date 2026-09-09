<?php declare(strict_types = 1);

/**
 * AccountsModuleExtension.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:AccountsModule!
 * @subpackage     DI
 * @since          1.0.0
 *
 * @date           30.11.20
 */

namespace FastyBird\Module\Accounts\DI;

use Contributte\Translation;
use Doctrine\Persistence;
use FastyBird\Core\Application\Boot as ApplicationBoot;
use FastyBird\Core\Application\DI as ApplicationDI;
use FastyBird\Core\Application\Documents as ApplicationDocuments;
use FastyBird\Module\Accounts\Commands;
use FastyBird\Module\Accounts\Controllers;
use FastyBird\Module\Accounts\Helpers;
use FastyBird\Module\Accounts\Hydrators;
use FastyBird\Module\Accounts\Middleware;
use FastyBird\Module\Accounts\Models;
use FastyBird\Module\Accounts\Router;
use FastyBird\Module\Accounts\Schemas;
use FastyBird\Module\Accounts\Security;
use FastyBird\Module\Accounts\Subscribers;
use FastyBird\Module\Accounts\Utilities;
use IPub\SlimRouter\Routing as SlimRouterRouting;
use Nette\Application;
use Nette\Bootstrap;
use Nette\DI;
use Nette\Schema;
use Nettrine\ORM as NettrineORM;
use stdClass;
use Tracy;
use function array_keys;
use function array_pop;
use function assert;
use function is_string;
use const DIRECTORY_SEPARATOR;

/**
 * Accounts module extension container
 *
 * @package        FastyBird:AccountsModule!
 * @subpackage     DI
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class AccountsExtension extends DI\CompilerExtension implements Translation\DI\TranslationProviderInterface
{

	public const NAME = 'fbAccountsModule';

	public static function register(
		ApplicationBoot\Configurator $config,
		string $extensionName = self::NAME,
	): void
	{
		$config->onCompile[] = static function (
			Bootstrap\Configurator $config,
			DI\Compiler $compiler,
		) use ($extensionName): void {
			$compiler->addExtension($extensionName, new self());
		};
	}

	public function getConfigSchema(): Schema\Schema
	{
		return Schema\Expect::structure([
			'apiPrefix' => Schema\Expect::bool(true),
		]);
	}

	public function loadConfiguration(): void
	{
		$builder = $this->getContainerBuilder();
		$configuration = $this->getConfig();
		assert($configuration instanceof stdClass);

		$builder->addDefinition($this->prefix('middlewares.access'), new DI\Definitions\ServiceDefinition())
			->setType(Middleware\Access::class);

		$builder->addDefinition($this->prefix('middlewares.urlFormat'), new DI\Definitions\ServiceDefinition())
			->setType(Middleware\UrlFormat::class)
			->setArguments(['usePrefix' => $configuration->apiPrefix])
			->addTag('middleware');

		$builder->addDefinition($this->prefix('router.routes'), new DI\Definitions\ServiceDefinition())
			->setType(Router\ApiRoutes::class)
			->setArguments(['usePrefix' => $configuration->apiPrefix]);

		$builder->addDefinition($this->prefix('router.validator'), new DI\Definitions\ServiceDefinition())
			->setType(Router\Validator::class);

		$builder->addDefinition($this->prefix('commands.create'), new DI\Definitions\ServiceDefinition())
			->setType(Commands\Accounts\Create::class);

		$builder->addDefinition($this->prefix('commands.initialize'), new DI\Definitions\ServiceDefinition())
			->setType(Commands\Install::class);

		$builder->addDefinition($this->prefix('models.accountsRepository'), new DI\Definitions\ServiceDefinition())
			->setType(Models\Entities\Accounts\AccountsRepository::class);

		$builder->addDefinition($this->prefix('models.emailsRepository'), new DI\Definitions\ServiceDefinition())
			->setType(Models\Entities\Emails\EmailsRepository::class);

		$builder->addDefinition($this->prefix('models.identitiesRepository'), new DI\Definitions\ServiceDefinition())
			->setType(Models\Entities\Identities\IdentitiesRepository::class);

		// Database managers
		$builder->addDefinition($this->prefix('models.accountsManager'), new DI\Definitions\ServiceDefinition())
			->setType(Models\Entities\Accounts\AccountsManager::class);

		$builder->addDefinition($this->prefix('models.emailsManager'), new DI\Definitions\ServiceDefinition())
			->setType(Models\Entities\Emails\EmailsManager::class);

		$builder->addDefinition($this->prefix('models.identitiesManager'), new DI\Definitions\ServiceDefinition())
			->setType(Models\Entities\Identities\IdentitiesManager::class);

		$builder->addDefinition($this->prefix('subscribers.entities'), new DI\Definitions\ServiceDefinition())
			->setType(Subscribers\ModuleEntities::class);

		$builder->addDefinition($this->prefix('subscribers.accountEntity'), new DI\Definitions\ServiceDefinition())
			->setType(Subscribers\AccountEntity::class);

		$builder->addDefinition($this->prefix('subscribers.emailEntity'), new DI\Definitions\ServiceDefinition())
			->setType(Subscribers\EmailEntity::class);

		$builder->addDefinition($this->prefix('controllers.session'), new DI\Definitions\ServiceDefinition())
			->setType(Controllers\SessionV1::class)
			->addTag('nette.inject');

		$builder->addDefinition($this->prefix('controllers.account'), new DI\Definitions\ServiceDefinition())
			->setType(Controllers\AccountV1::class)
			->addTag('nette.inject');

		$builder->addDefinition($this->prefix('controllers.accountEmails'), new DI\Definitions\ServiceDefinition())
			->setType(Controllers\AccountEmailsV1::class)
			->addTag('nette.inject');

		$builder->addDefinition($this->prefix('controllers.accountIdentities'), new DI\Definitions\ServiceDefinition())
			->setType(Controllers\AccountIdentitiesV1::class)
			->addTag('nette.inject');

		$builder->addDefinition($this->prefix('controllers.accounts'), new DI\Definitions\ServiceDefinition())
			->setType(Controllers\AccountsV1::class)
			->addTag('nette.inject');

		$builder->addDefinition($this->prefix('controllers.emails'), new DI\Definitions\ServiceDefinition())
			->setType(Controllers\EmailsV1::class)
			->addTag('nette.inject');

		$builder->addDefinition($this->prefix('controllers.identities'), new DI\Definitions\ServiceDefinition())
			->setType(Controllers\IdentitiesV1::class)
			->addTag('nette.inject');

		$builder->addDefinition($this->prefix('controllers.roles'), new DI\Definitions\ServiceDefinition())
			->setType(Controllers\RolesV1::class)
			->addTag('nette.inject');

		$builder->addDefinition($this->prefix('controllers.roleChildren'), new DI\Definitions\ServiceDefinition())
			->setType(Controllers\RoleChildrenV1::class)
			->addTag('nette.inject');

		$builder->addDefinition($this->prefix('controllers.public'), new DI\Definitions\ServiceDefinition())
			->setType(Controllers\PublicV1::class)
			->addTag('nette.inject');

		$builder->addDefinition($this->prefix('schemas.account'), new DI\Definitions\ServiceDefinition())
			->setType(Schemas\Accounts\Account::class);

		$builder->addDefinition($this->prefix('schemas.email'), new DI\Definitions\ServiceDefinition())
			->setType(Schemas\Emails\Email::class);

		$builder->addDefinition($this->prefix('schemas.identity'), new DI\Definitions\ServiceDefinition())
			->setType(Schemas\Identities\Identity::class);

		$builder->addDefinition($this->prefix('schemas.role'), new DI\Definitions\ServiceDefinition())
			->setType(Schemas\Roles\Role::class);

		$builder->addDefinition($this->prefix('schemas.session'), new DI\Definitions\ServiceDefinition())
			->setType(Schemas\Sessions\Session::class);

		$builder->addDefinition($this->prefix('hydrators.accounts.profile'), new DI\Definitions\ServiceDefinition())
			->setType(Hydrators\Accounts\ProfileAccount::class);

		$builder->addDefinition($this->prefix('hydrators.accounts'), new DI\Definitions\ServiceDefinition())
			->setType(Hydrators\Accounts\Account::class);

		$builder->addDefinition($this->prefix('hydrators.emails.profile'), new DI\Definitions\ServiceDefinition())
			->setType(Hydrators\Emails\ProfileEmail::class);

		$builder->addDefinition($this->prefix('hydrators.emails.email'), new DI\Definitions\ServiceDefinition())
			->setType(Hydrators\Emails\Email::class);

		$builder->addDefinition($this->prefix('hydrators.identities.profile'), new DI\Definitions\ServiceDefinition())
			->setType(Hydrators\Identities\Identity::class);

		$builder->addDefinition($this->prefix('hydrators.role'), new DI\Definitions\ServiceDefinition())
			->setType(Hydrators\Roles\Role::class);

		$builder->addDefinition($this->prefix('security.hash'), new DI\Definitions\ServiceDefinition())
			->setType(Helpers\SecurityHash::class);

		$builder->addDefinition($this->prefix('security.identityFactory'), new DI\Definitions\ServiceDefinition())
			->setType(Security\IdentityFactory::class);

		$builder->addDefinition($this->prefix('security.authenticator'), new DI\Definitions\ServiceDefinition())
			->setType(Security\Authenticator::class);

		$builder->addDefinition('security.user', new DI\Definitions\ServiceDefinition())
			->setType(Security\User::class);
	}

	/**
	 * @throws DI\MissingServiceException
	 * @throws DI\NotAllowedDuringResolvingException
	 */
	public function beforeCompile(): void
	{
		parent::beforeCompile();

		$builder = $this->getContainerBuilder();

		/**
		 * DOCTRINE ENTITIES
		 */

		$services = $builder->findByTag(NettrineORM\DI\OrmAttributesExtension::DRIVER_TAG);

		if ($services !== []) {
			$services = array_keys($services);
			$ormAttributeDriverServiceName = array_pop($services);

			$ormAttributeDriverService = $builder->getDefinition($ormAttributeDriverServiceName);

			if ($ormAttributeDriverService instanceof DI\Definitions\ServiceDefinition) {
				$ormAttributeDriverService->addSetup(
					'addPaths',
					[[__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'Entities']],
				);

				$ormAttributeDriverChainService = $builder->getDefinitionByType(
					Persistence\Mapping\Driver\MappingDriverChain::class,
				);

				if ($ormAttributeDriverChainService instanceof DI\Definitions\ServiceDefinition) {
					$ormAttributeDriverChainService->addSetup('addDriver', [
						$ormAttributeDriverService,
						'FastyBird\Module\Accounts\Entities',
					]);
				}
			}
		}

		/**
		 * APPLICATION DOCUMENTS
		 */

		$services = $builder->findByTag(ApplicationDI\ApplicationExtension::DRIVER_TAG);

		if ($services !== []) {
			$services = array_keys($services);
			$documentAttributeDriverServiceName = array_pop($services);

			$documentAttributeDriverService = $builder->getDefinition($documentAttributeDriverServiceName);

			if ($documentAttributeDriverService instanceof DI\Definitions\ServiceDefinition) {
				$documentAttributeDriverService->addSetup(
					'addPaths',
					[[__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'Documents']],
				);

				$documentAttributeDriverChainService = $builder->getDefinitionByType(
					ApplicationDocuments\Mapping\Driver\MappingDriverChain::class,
				);

				if ($documentAttributeDriverChainService instanceof DI\Definitions\ServiceDefinition) {
					$documentAttributeDriverChainService->addSetup('addDriver', [
						$documentAttributeDriverService,
						'FastyBird\Module\Accounts\Documents',
					]);
				}
			}
		}

		/**
		 * ROUTES
		 */

		$routerService = $builder->getDefinitionByType(SlimRouterRouting\Router::class);

		if ($routerService instanceof DI\Definitions\ServiceDefinition) {
			$routerService->addSetup('?->registerRoutes(?)', [
				$builder->getDefinitionByType(Router\ApiRoutes::class),
				$routerService,
			]);
		}

		$appRouterServiceName = $builder->getByType(Application\Routers\RouteList::class);
		assert(is_string($appRouterServiceName));
		$appRouterService = $builder->getDefinition($appRouterServiceName);
		assert($appRouterService instanceof DI\Definitions\ServiceDefinition);

		$appRouterService->addSetup([Router\AppRouter::class, 'createRouter'], [$appRouterService]);

		/**
		 * UI
		 */

		$presenterFactoryService = $builder->getDefinitionByType(Application\IPresenterFactory::class);

		if ($presenterFactoryService instanceof DI\Definitions\ServiceDefinition) {
			$presenterFactoryService->addSetup('setMapping', [[
				'Accounts' => 'FastyBird\Module\Accounts\Presenters\*Presenter',
			]]);
		}

		/**
		 * DEBUGGING
		 */

		if ($builder->getByType(Tracy\Bar::class) !== null) {
			$tracyService = $builder->getDefinitionByType(Tracy\Bar::class);

			if ($tracyService instanceof DI\Definitions\ServiceDefinition) {
				$tracyPanel = $builder->addDefinition(
					$this->prefix('security.userPanel'),
					new DI\Definitions\ServiceDefinition(),
				)
					->setType(Utilities\UserPanel::class);

				$tracyService->addSetup('addPanel', [$tracyPanel]);
			}
		}
	}

	/**
	 * @return array<string>
	 */
	public function getTranslationResources(): array
	{
		return [
			__DIR__ . '/../Translations/',
		];
	}

}
