<?php declare(strict_types = 1);

/**
 * MiniServerExtension.php
 *
 * @license        More in license.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:MiniServer!
 * @subpackage     DI
 * @since          0.1.0
 *
 * @date           22.02.21
 */

namespace FastyBird\MiniServer\DI;

use Doctrine\Persistence;
use FastyBird\MiniServer\Application;
use FastyBird\MiniServer\Commands;
use FastyBird\MiniServer\Controllers;
use FastyBird\MiniServer\Entities;
use FastyBird\MiniServer\Events;
use FastyBird\MiniServer\Exceptions;
//use FastyBird\MiniServer\Middleware;
use FastyBird\MiniServer\Models;
use FastyBird\MiniServer\Subscribers;
use IPub\DoctrineCrud;
use IPub\WebSockets;
use Nette\DI;
use Nette\PhpGenerator;
use Nette\Schema;
use Psr\EventDispatcher;
use stdClass;

/**
 * WS server plugin
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
	public function getConfigSchema(): Schema\Schema
	{
		return Schema\Expect::structure([
			'server' => Schema\Expect::structure([
				'web'     => Schema\Expect::structure([
					'address' => Schema\Expect::string('0.0.0.0'),
					'port'    => Schema\Expect::int(80),
				]),
				'sockets' => Schema\Expect::structure([
					'address' => Schema\Expect::string('0.0.0.0'),
					'port'    => Schema\Expect::int(8080),
				]),
			]),
		]);
	}

	/**
	 * {@inheritDoc}
	 */
	public function loadConfiguration(): void
	{
		$builder = $this->getContainerBuilder();
		/** @var stdClass $configuration */
		$configuration = $this->getConfig();

		// Console commands
		$builder->addDefinition($this->prefix('command.initialize'), new DI\Definitions\ServiceDefinition())
			->setType(Commands\InitializeCommand::class);

		$builder->addDefinition($this->prefix('command.createApiKey'), new DI\Definitions\ServiceDefinition())
			->setType(Commands\ApiKeys\CreateCommand::class);

		$builder->addDefinition($this->prefix('command.wsServer'), new DI\Definitions\ServiceDefinition())
			->setType(Commands\WsServerCommand::class)
			->setArguments([
				'serverAddress' => $configuration->server->sockets->address,
				'serverPort'    => $configuration->server->sockets->port,
			]);

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
		$builder->addDefinition($this->prefix('states.devicePropertyRepository'), new DI\Definitions\ServiceDefinition())
			->setType(Models\DevicePropertyRepository::class)
			->setArgument('stateRepositoryFactory', '@fbRedisDbStoragePlugin.model.properties.stateRepositoryFactory');

		$builder->addDefinition($this->prefix('states.channelPropertyRepository'), new DI\Definitions\ServiceDefinition())
			->setType(Models\ChannelPropertyRepository::class)
			->setArgument('stateRepositoryFactory', '@fbRedisDbStoragePlugin.model.properties.stateRepositoryFactory');

		$builder->addDefinition($this->prefix('states.triggerActionRepository'), new DI\Definitions\ServiceDefinition())
			->setType(Models\TriggerActionRepository::class)
			->setArgument('stateRepositoryFactory', '@fbRedisDbStoragePlugin.model.triggers.stateRepositoryFactory');

		$builder->addDefinition($this->prefix('states.triggerConditionRepository'), new DI\Definitions\ServiceDefinition())
			->setType(Models\TriggerConditionRepository::class)
			->setArgument('stateRepositoryFactory', '@fbRedisDbStoragePlugin.model.triggers.stateRepositoryFactory');

		// Controllers
		$builder->addDefinition($this->prefix('controllers.exchange'), new DI\Definitions\ServiceDefinition())
			->setType(Controllers\ExchangeController::class)
			->addTag('nette.inject');

		// Application
		if ($this->cliMode === false) {
			$builder->addDefinition($this->prefix('application.application'), new DI\Definitions\ServiceDefinition())
				->setType(Application\Application::class);
		}

		// Subscribers
		$builder->addDefinition($this->prefix('subscribers.application'), new DI\Definitions\ServiceDefinition())
			->setType(Subscribers\ApplicationSubscriber::class);

		$builder->addDefinition($this->prefix('subscribers.entities'), new DI\Definitions\ServiceDefinition())
			->setType(Subscribers\EntitiesSubscriber::class);

		$builder->addDefinition($this->prefix('subscribers.ws'), new DI\Definitions\ServiceDefinition())
			->setType(Subscribers\WsSubscriber::class);
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
		 * Events bridges
		 */

		if ($builder->getByType(EventDispatcher\EventDispatcherInterface::class) === null) {
			throw new Exceptions\LogicException(sprintf('Service of type "%s" is needed. Please register it.', EventDispatcher\EventDispatcherInterface::class));
		}

		$dispatcher = $builder->getDefinition($builder->getByType(EventDispatcher\EventDispatcherInterface::class));

		$socketWrapperServiceName = $builder->getByType(WebSockets\Server\Wrapper::class);
		assert(is_string($socketWrapperServiceName));

		$socketWrapperService = $builder->getDefinition($socketWrapperServiceName);
		assert($socketWrapperService instanceof DI\Definitions\ServiceDefinition);

		$socketWrapperService->addSetup('?->onClientConnected[] = function() {?->dispatch(new ?(...func_get_args()));}', [
			'@self',
			$dispatcher,
			new PhpGenerator\PhpLiteral(Events\WsClientConnectedEvent::class),
		]);

		$socketWrapperService->addSetup('?->onIncomingMessage[] = function() {?->dispatch(new ?(...func_get_args()));}', [
			'@self',
			$dispatcher,
			new PhpGenerator\PhpLiteral(Events\WsIncomingMessage::class),
		]);
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
