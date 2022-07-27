<?php declare(strict_types = 1);

/**
 * MiniServerExtension.php
 *
 * @license        More in license.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:MiniServer!
 * @subpackage     DI
 * @since          0.2.0
 *
 * @date           15.01.22
 */

namespace FastyBird\MiniServer\DI;

use Doctrine\Persistence;
use FastyBird\JsonApi\Middleware as JsonApiMiddleware;
use FastyBird\MiniServer\Application;
use FastyBird\MiniServer\Commands;
use FastyBird\MiniServer\Entities;
use FastyBird\MiniServer\Exceptions;
use FastyBird\MiniServer\Models;
use FastyBird\MiniServer\Subscribers;
use IPub\DoctrineCrud;
use IPub\SlimRouter\Routing as SlimRouterRouting;
use Nette\DI;
use Nette\PhpGenerator;

//use FastyBird\MiniServer\Middleware;

/**
 * MiniServer application
 *
 * @package        FastyBird:MiniServer!
 * @subpackage     DI
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class MiniServerExtension extends DI\CompilerExtension
{

	/** @var bool */
	private bool $cliMode;

	public function __construct(bool $cliMode = false)
	{
		if (func_num_args() <= 0) {
			throw new Exceptions\InvalidArgumentException(sprintf('Provide CLI mode, e.q. %s(%%consoleMode%%).', self::class));
		}

		$this->cliMode = $cliMode;
	}

	/**
	 * {@inheritDoc}
	 */
	public function loadConfiguration(): void
	{
		$builder = $this->getContainerBuilder();

		// Console commands
		$builder->addDefinition($this->prefix('command.initialize'), new DI\Definitions\ServiceDefinition())
			->setType(Commands\InitializeCommand::class);

		$builder->addDefinition($this->prefix('command.createApiKey'), new DI\Definitions\ServiceDefinition())
			->setType(Commands\ApiKeys\CreateCommand::class);

		// HTTP server
		// $builder->addDefinition($this->prefix('middleware.apiKeyValidator'), new DI\Definitions\ServiceDefinition())
		//	->setType(Middleware\ApiKeyValidatorMiddleware::class)
		//	->addTag('middleware', ['priority' => 30]);

		// Database repositories
		$builder->addDefinition($this->prefix('models.apiKeyRepository'), new DI\Definitions\ServiceDefinition())
			->setType(Models\ApiKeys\KeyRepository::class);

		// Database managers
		$builder->addDefinition($this->prefix('models.apiKeysManager'), new DI\Definitions\ServiceDefinition())
			->setType(Models\ApiKeys\KeysManager::class)
			->setArgument('entityCrud', '__placeholder__');

		// States management
		$builder->addDefinition($this->prefix('states.connectorPropertyRepository'), new DI\Definitions\ServiceDefinition())
			->setType(Models\States\ConnectorPropertiesRepository::class)
			->setArgument('stateRepositoryFactory', '@fbRedisDbStoragePlugin.model.properties.stateRepositoryFactory');

		$builder->addDefinition($this->prefix('states.devicePropertyRepository'), new DI\Definitions\ServiceDefinition())
			->setType(Models\States\DevicePropertiesRepository::class)
			->setArgument('stateRepositoryFactory', '@fbRedisDbStoragePlugin.model.properties.stateRepositoryFactory');

		$builder->addDefinition($this->prefix('states.channelPropertyRepository'), new DI\Definitions\ServiceDefinition())
			->setType(Models\States\ChannelPropertiesRepository::class)
			->setArgument('stateRepositoryFactory', '@fbRedisDbStoragePlugin.model.properties.stateRepositoryFactory');

		$builder->addDefinition($this->prefix('states.triggerActionRepository'), new DI\Definitions\ServiceDefinition())
			->setType(Models\States\TriggerActionsRepository::class)
			->setArgument('stateRepositoryFactory', '@fbRedisDbStoragePlugin.model.triggers.stateRepositoryFactory');

		$builder->addDefinition($this->prefix('states.triggerConditionRepository'), new DI\Definitions\ServiceDefinition())
			->setType(Models\States\TriggerConditionsRepository::class)
			->setArgument('stateRepositoryFactory', '@fbRedisDbStoragePlugin.model.triggers.stateRepositoryFactory');

		$builder->addDefinition($this->prefix('states.connectorPropertiesManager'), new DI\Definitions\ServiceDefinition())
			->setType(Models\States\ConnectorPropertiesManager::class)
			->setArgument('statesManagerFactory', '@fbRedisDbStoragePlugin.model.properties.statesManagerFactory');

		$builder->addDefinition($this->prefix('states.devicePropertiesManager'), new DI\Definitions\ServiceDefinition())
			->setType(Models\States\DevicePropertiesManager::class)
			->setArgument('statesManagerFactory', '@fbRedisDbStoragePlugin.model.properties.statesManagerFactory');

		$builder->addDefinition($this->prefix('states.channelPropertiesManager'), new DI\Definitions\ServiceDefinition())
			->setType(Models\States\ChannelPropertiesManager::class)
			->setArgument('statesManagerFactory', '@fbRedisDbStoragePlugin.model.properties.statesManagerFactory');

		// Application
		if ($this->cliMode === false) {
			$builder->addDefinition($this->prefix('application.application'), new DI\Definitions\ServiceDefinition())
				->setType(Application\Application::class);
		}

		// Subscribers
		$builder->addDefinition($this->prefix('subscribers.server.web'), new DI\Definitions\ServiceDefinition())
			->setType(Subscribers\WebServerSubscriber::class);

		$builder->addDefinition($this->prefix('subscribers.server.ws'), new DI\Definitions\ServiceDefinition())
			->setType(Subscribers\WsServerSubscriber::class);

		$builder->addDefinition($this->prefix('subscribers.entities'), new DI\Definitions\ServiceDefinition())
			->setType(Subscribers\EntitiesSubscriber::class);
	}

	/**
	 * Build bridge into nette application events
	 */
	public function beforeCompile(): void
	{
		$builder = $this->getContainerBuilder();

		/**
		 * Doctrine entities
		 */

		$ormAnnotationDriverService = $builder->getDefinition('nettrineOrmAnnotations.annotationDriver');

		if ($ormAnnotationDriverService instanceof DI\Definitions\ServiceDefinition) {
			$ormAnnotationDriverService->addSetup('addPaths', [[__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'Entities']]);
		}

		$ormAnnotationDriverChainService = $builder->getDefinitionByType(Persistence\Mapping\Driver\MappingDriverChain::class);

		if ($ormAnnotationDriverChainService instanceof DI\Definitions\ServiceDefinition) {
			$ormAnnotationDriverChainService->addSetup('addDriver', [
				$ormAnnotationDriverService,
				'FastyBird\MiniServer\Entities',
			]);
		}

		/**
		 * Router middlewares
		 */
		$routerService = $builder->getDefinitionByType(SlimRouterRouting\Router::class);

		if ($routerService instanceof DI\Definitions\ServiceDefinition) {
			$routerService->addSetup('?->addMiddleware(?)', [
				$routerService,
				$builder->getDefinitionByType(JsonApiMiddleware\JsonApiMiddleware::class),
			]);
		}
	}

	/**
	 * {@inheritDoc}
	 */
	public function afterCompile(
		PhpGenerator\ClassType $class
	): void {
		$builder = $this->getContainerBuilder();

		$entityFactoryServiceName = $builder->getByType(DoctrineCrud\Crud\IEntityCrudFactory::class, true);

		$apiKeysManagerService = $class->getMethod('createService' . ucfirst($this->name) . '__models__apiKeysManager');
		$apiKeysManagerService->setBody('return new ' . Models\ApiKeys\KeysManager::class . '($this->getService(\'' . $entityFactoryServiceName . '\')->create(\'' . Entities\ApiKeys\Key::class . '\'));');
	}

}
