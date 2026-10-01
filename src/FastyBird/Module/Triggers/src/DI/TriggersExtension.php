<?php declare(strict_types = 1);

/**
 * TriggersExtension.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:TriggersModule!
 * @subpackage     DI
 * @since          1.0.0
 *
 * @date           29.11.20
 */

namespace FastyBird\Module\Triggers\DI;

use Contributte\Translation;
use FastyBird\Core\Boot;
use FastyBird\Core\Documents;
use FastyBird\Core\Documents\DI as DocumentsDI;
use FastyBird\Core\Http\Routing;
use FastyBird\Module\Triggers\Commands;
use FastyBird\Module\Triggers\Controllers;
use FastyBird\Module\Triggers\Hydrators;
use FastyBird\Module\Triggers\Middleware;
use FastyBird\Module\Triggers\Models;
use FastyBird\Module\Triggers\Router;
use FastyBird\Module\Triggers\Schemas;
use FastyBird\Module\Triggers\Subscribers;
use Nette\Bootstrap;
use Nette\DI as NetteDI;
use Nette\Schema;
use Nettrine\ORM as NettrineORM;
use stdClass;
use function array_keys;
use function array_pop;
use function assert;
use const DIRECTORY_SEPARATOR;

/**
 * Triggers module extension container
 *
 * @package        FastyBird:TriggersModule!
 * @subpackage     DI
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class TriggersExtension extends NetteDI\CompilerExtension implements Translation\DI\TranslationProviderInterface
{

	public const NAME = 'fbTriggersModule';

	public const TRIGGER_TYPE_TAG = 'trigger_type';

	public static function register(
		Boot\Configurator $config,
		string $extensionName = self::NAME,
	): void
	{
		$config->onCompile[] = static function (
			Bootstrap\Configurator $config,
			NetteDI\Compiler $compiler,
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

		$builder->addDefinition($this->prefix('middleware.access'), new NetteDI\Definitions\ServiceDefinition())
			->setType(Middleware\Access::class);

		$builder->addDefinition($this->prefix('router.routes'), new NetteDI\Definitions\ServiceDefinition())
			->setType(Router\ApiRoutes::class)
			->setArguments(['usePrefix' => $configuration->apiPrefix]);

		$builder->addDefinition($this->prefix('router.validator'), new NetteDI\Definitions\ServiceDefinition())
			->setType(Router\Validator::class);

		$builder->addDefinition($this->prefix('models.triggersRepository'), new NetteDI\Definitions\ServiceDefinition())
			->setType(Models\Entities\Triggers\TriggersRepository::class);

		$builder->addDefinition(
			$this->prefix('models.triggeControlsRepository'),
			new NetteDI\Definitions\ServiceDefinition(),
		)
			->setType(Models\Entities\Triggers\Controls\ControlsRepository::class);

		$builder->addDefinition($this->prefix('models.actionsRepository'), new NetteDI\Definitions\ServiceDefinition())
			->setType(Models\Entities\Actions\ActionsRepository::class);

		$builder->addDefinition(
			$this->prefix('models.conditionsRepository'),
			new NetteDI\Definitions\ServiceDefinition(),
		)
			->setType(Models\Entities\Conditions\ConditionsRepository::class);

		$builder->addDefinition(
			$this->prefix('models.notificationsRepository'),
			new NetteDI\Definitions\ServiceDefinition(),
		)
			->setType(Models\Entities\Notifications\NotificationsRepository::class);

		$builder->addDefinition($this->prefix('models.triggersManager'), new NetteDI\Definitions\ServiceDefinition())
			->setType(Models\Entities\Triggers\TriggersManager::class);

		$builder->addDefinition(
			$this->prefix('models.triggersControlsManager'),
			new NetteDI\Definitions\ServiceDefinition(),
		)
			->setType(Models\Entities\Triggers\Controls\ControlsManager::class);

		$builder->addDefinition($this->prefix('models.actionsManager'), new NetteDI\Definitions\ServiceDefinition())
			->setType(Models\Entities\Actions\ActionsManager::class);

		$builder->addDefinition($this->prefix('models.conditionsManager'), new NetteDI\Definitions\ServiceDefinition())
			->setType(Models\Entities\Conditions\ConditionsManager::class);

		$builder->addDefinition(
			$this->prefix('models.notificationsManager'),
			new NetteDI\Definitions\ServiceDefinition(),
		)
			->setType(Models\Entities\Notifications\NotificationsManager::class);

		$builder->addDefinition(
			$this->prefix('subscribers.notificationEntity'),
			new NetteDI\Definitions\ServiceDefinition(),
		)
			->setType(Subscribers\NotificationEntity::class);

		$builder->addDefinition($this->prefix('subscribers.entities'), new NetteDI\Definitions\ServiceDefinition())
			->setType(Subscribers\ModuleEntities::class);

		$builder->addDefinition($this->prefix('controllers.triggers'), new NetteDI\Definitions\ServiceDefinition())
			->setType(Controllers\TriggersV1::class)
			->addTag('nette.inject');

		$builder->addDefinition($this->prefix('controllers.actions'), new NetteDI\Definitions\ServiceDefinition())
			->setType(Controllers\ActionsV1::class)
			->addTag('nette.inject');

		$builder->addDefinition($this->prefix('controllers.conditions'), new NetteDI\Definitions\ServiceDefinition())
			->setType(Controllers\ConditionsV1::class)
			->addTag('nette.inject');

		$builder->addDefinition($this->prefix('controllers.notifications'), new NetteDI\Definitions\ServiceDefinition())
			->setType(Controllers\NotificationsV1::class)
			->addTag('nette.inject');

		$builder->addDefinition(
			$this->prefix('controllers.triggersControls'),
			new NetteDI\Definitions\ServiceDefinition(),
		)
			->setType(Controllers\TriggerControlsV1::class)
			->addTag('nette.inject');

		$builder->addDefinition(
			$this->prefix('schemas.triggers.automatic'),
			new NetteDI\Definitions\ServiceDefinition(),
		)
			->setType(Schemas\Triggers\Automatic::class);

		$builder->addDefinition($this->prefix('schemas.triggers.manual'), new NetteDI\Definitions\ServiceDefinition())
			->setType(Schemas\Triggers\Manual::class);

		$builder->addDefinition($this->prefix('schemas.trigger.control'), new NetteDI\Definitions\ServiceDefinition())
			->setType(Schemas\Triggers\Controls\Control::class);

		$builder->addDefinition(
			$this->prefix('schemas.notifications.email'),
			new NetteDI\Definitions\ServiceDefinition(),
		)
			->setType(Schemas\Notifications\Email::class);

		$builder->addDefinition($this->prefix('schemas.notifications.sms'), new NetteDI\Definitions\ServiceDefinition())
			->setType(Schemas\Notifications\Sms::class);

		$builder->addDefinition(
			$this->prefix('hydrators.triggers.automatic'),
			new NetteDI\Definitions\ServiceDefinition(),
		)
			->setType(Hydrators\Triggers\AutomaticTrigger::class);

		$builder->addDefinition($this->prefix('hydrators.triggers.manual'), new NetteDI\Definitions\ServiceDefinition())
			->setType(Hydrators\Triggers\ManualTrigger::class);

		$builder->addDefinition(
			$this->prefix('hydrators.notifications.email'),
			new NetteDI\Definitions\ServiceDefinition(),
		)
			->setType(Hydrators\Notifications\Email::class);

		$builder->addDefinition(
			$this->prefix('hydrators.notifications.sms'),
			new NetteDI\Definitions\ServiceDefinition(),
		)
			->setType(Hydrators\Notifications\Sms::class);

		$builder->addDefinition(
			$this->prefix('states.repositories.actions'),
			new NetteDI\Definitions\ServiceDefinition(),
		)
			->setType(Models\States\ActionsRepository::class);

		$builder->addDefinition(
			$this->prefix('states.repositories.conditions'),
			new NetteDI\Definitions\ServiceDefinition(),
		)
			->setType(Models\States\ConditionsRepository::class);

		$builder->addDefinition($this->prefix('states.managers.actions'), new NetteDI\Definitions\ServiceDefinition())
			->setType(Models\States\ActionsManager::class);

		$builder->addDefinition(
			$this->prefix('states.managers.conditions'),
			new NetteDI\Definitions\ServiceDefinition(),
		)
			->setType(Models\States\ConditionsManager::class);

		$builder->addDefinition($this->prefix('commands.initialize'), new NetteDI\Definitions\ServiceDefinition())
			->setType(Commands\Install::class);
	}

	/**
	 * @throws NetteDI\MissingServiceException
	 */
	public function beforeCompile(): void
	{
		parent::beforeCompile();

		$builder = $this->getContainerBuilder();

		/**
		 * DOCTRINE ENTITIES
		 */

		// nettrine/orm 0.10 tags the per-manager MappingDriverChain, not an AttributeDriver, so
		// there is no service left to call addPaths() on. MappingHelper is nettrine's own
		// entry point for contributing a mapping to a manager from another extension.
		NettrineORM\DI\Helpers\MappingHelper::of($this)->addAttribute(
			'default',
			'FastyBird\Module\Triggers\Entities',
			__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'Entities',
		);

		/**
		 * APPLICATION DOCUMENTS
		 */

		$services = $builder->findByTag(DocumentsDI\DocumentsExtension::DRIVER_TAG);

		if ($services !== []) {
			$services = array_keys($services);
			$documentAttributeDriverServiceName = array_pop($services);

			$documentAttributeDriverService = $builder->getDefinition($documentAttributeDriverServiceName);

			if ($documentAttributeDriverService instanceof NetteDI\Definitions\ServiceDefinition) {
				$documentAttributeDriverService->addSetup(
					'addPaths',
					[[__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'Documents']],
				);

				$documentAttributeDriverChainService = $builder->getDefinitionByType(
					Documents\Mapping\Driver\MappingDriverChain::class,
				);

				if ($documentAttributeDriverChainService instanceof NetteDI\Definitions\ServiceDefinition) {
					$documentAttributeDriverChainService->addSetup('addDriver', [
						$documentAttributeDriverService,
						'FastyBird\Module\Triggers\Documents',
					]);
				}
			}
		}

		/**
		 * Routes
		 */

		$routerService = $builder->getDefinitionByType(Routing\Router::class);

		if ($routerService instanceof NetteDI\Definitions\ServiceDefinition) {
			$routerService->addSetup(
				'?->registerRoutes(?)',
				[$builder->getDefinitionByType(Router\ApiRoutes::class), $routerService],
			);
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
