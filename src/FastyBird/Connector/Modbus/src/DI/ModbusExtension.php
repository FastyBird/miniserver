<?php declare(strict_types = 1);

/**
 * ModbusExtension.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ModbusConnector!
 * @subpackage     DI
 * @since          1.0.0
 *
 * @date           30.01.22
 */

namespace FastyBird\Connector\Modbus\DI;

use Contributte\Translation;
use FastyBird\Connector\Modbus;
use FastyBird\Connector\Modbus\API;
use FastyBird\Connector\Modbus\Clients;
use FastyBird\Connector\Modbus\Commands;
use FastyBird\Connector\Modbus\Connector;
use FastyBird\Connector\Modbus\Entities;
use FastyBird\Connector\Modbus\Helpers;
use FastyBird\Connector\Modbus\Hydrators;
use FastyBird\Connector\Modbus\Queue;
use FastyBird\Connector\Modbus\Schemas;
use FastyBird\Connector\Modbus\Subscribers;
use FastyBird\Connector\Modbus\Writers;
use FastyBird\Core\Boot;
use FastyBird\Core\Documents;
use FastyBird\Core\Documents\DI as DocumentsDI;
use FastyBird\Core\Exchange\DI as ExchangeDI;
use FastyBird\Module\Devices\DI as DevicesDI;
use Nette\Bootstrap;
use Nette\DI as NetteDI;
use Nettrine\Fixtures as NettrineFixtures;
use Nettrine\ORM as NettrineORM;
use function array_keys;
use function array_pop;
use const DIRECTORY_SEPARATOR;

/**
 * Modbus connector
 *
 * @package        FastyBird:ModbusConnector!
 * @subpackage     DI
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class ModbusExtension extends NetteDI\CompilerExtension implements Translation\DI\TranslationProviderInterface
{

	public const NAME = 'fbModbusConnector';

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

	/**
	 * @throws NetteDI\NotAllowedDuringResolvingException
	 */
	public function loadConfiguration(): void
	{
		$builder = $this->getContainerBuilder();

		$logger = $builder->addDefinition($this->prefix('logger'), new NetteDI\Definitions\ServiceDefinition())
			->setType(Modbus\Logger::class)
			->setAutowired(false);

		/**
		 * WRITERS
		 */

		$builder->addFactoryDefinition($this->prefix('writers.event'))
			->setImplement(Writers\EventFactory::class)
			->getResultDefinition()
			->setType(Writers\Event::class)
			->setArguments([
				'logger' => $logger,
			]);

		$builder->addFactoryDefinition($this->prefix('writers.exchange'))
			->setImplement(Writers\ExchangeFactory::class)
			->getResultDefinition()
			->setType(Writers\Exchange::class)
			->setArguments([
				'logger' => $logger,
			])
			->addTag(ExchangeDI\ExchangeExtension::CONSUMER_STATE, false);

		/**
		 * CLIENTS
		 */

		$builder->addFactoryDefinition($this->prefix('clients.rtu'))
			->setImplement(Clients\RtuFactory::class)
			->getResultDefinition()
			->setType(Clients\Rtu::class)
			->setArguments([
				'logger' => $logger,
			]);

		$builder->addFactoryDefinition($this->prefix('clients.tcp'))
			->setImplement(Clients\TcpFactory::class)
			->getResultDefinition()
			->setType(Clients\Tcp::class)
			->setArguments([
				'logger' => $logger,
			]);

		/**
		 * API
		 */

		$builder->addDefinition($this->prefix('api.connectionsManager'), new NetteDI\Definitions\ServiceDefinition())
			->setType(API\ConnectionManager::class);

		$builder->addFactoryDefinition($this->prefix('api.rtu'))
			->setImplement(API\RtuFactory::class)
			->getResultDefinition()
			->setType(API\Rtu::class);

		$builder->addFactoryDefinition($this->prefix('api.tcp'))
			->setImplement(API\TcpFactory::class)
			->getResultDefinition()
			->setType(API\Tcp::class);

		$builder->addDefinition($this->prefix('api.transformer'), new NetteDI\Definitions\ServiceDefinition())
			->setType(API\Transformer::class);

		/**
		 * MESSAGES QUEUE
		 */

		$builder->addDefinition(
			$this->prefix('queue.consumers.store.deviceConnectionState'),
			new NetteDI\Definitions\ServiceDefinition(),
		)
			->setType(Queue\Consumers\StoreDeviceConnectionState::class)
			->setArguments([
				'logger' => $logger,
			]);

		$builder->addDefinition(
			$this->prefix('queue.consumers.store.channelPropertyState'),
			new NetteDI\Definitions\ServiceDefinition(),
		)
			->setType(Queue\Consumers\StoreChannelPropertyState::class)
			->setArguments([
				'logger' => $logger,
			]);

		$builder->addDefinition(
			$this->prefix('queue.consumers.write.channelPropertyState'),
			new NetteDI\Definitions\ServiceDefinition(),
		)
			->setType(Queue\Consumers\WriteChannelPropertyState::class)
			->setArguments([
				'logger' => $logger,
			]);

		$builder->addDefinition(
			$this->prefix('queue.consumers'),
			new NetteDI\Definitions\ServiceDefinition(),
		)
			->setType(Queue\Consumers::class)
			->setArguments([
				'consumers' => $builder->findByType(Queue\Consumer::class),
				'logger' => $logger,
			]);

		$builder->addDefinition(
			$this->prefix('queue.queue'),
			new NetteDI\Definitions\ServiceDefinition(),
		)
			->setType(Queue\Queue::class)
			->setArguments([
				'logger' => $logger,
			]);

		/**
		 * SUBSCRIBERS
		 */

		$builder->addDefinition($this->prefix('subscribers.properties'), new NetteDI\Definitions\ServiceDefinition())
			->setType(Subscribers\Properties::class);

		$builder->addDefinition($this->prefix('subscribers.controls'), new NetteDI\Definitions\ServiceDefinition())
			->setType(Subscribers\Controls::class);

		/**
		 * JSON-API SCHEMAS
		 */

		$builder->addDefinition($this->prefix('schemas.connector'), new NetteDI\Definitions\ServiceDefinition())
			->setType(Schemas\Connectors\Connector::class);

		$builder->addDefinition($this->prefix('schemas.device'), new NetteDI\Definitions\ServiceDefinition())
			->setType(Schemas\Devices\Device::class);

		$builder->addDefinition($this->prefix('schemas.channel'), new NetteDI\Definitions\ServiceDefinition())
			->setType(Schemas\Channels\Channel::class);

		/**
		 * JSON-API HYDRATORS
		 */

		$builder->addDefinition($this->prefix('hydrators.connector'), new NetteDI\Definitions\ServiceDefinition())
			->setType(Hydrators\Connectors\Connector::class);

		$builder->addDefinition($this->prefix('hydrators.device'), new NetteDI\Definitions\ServiceDefinition())
			->setType(Hydrators\Devices\Device::class);

		$builder->addDefinition($this->prefix('hydrators.channel'), new NetteDI\Definitions\ServiceDefinition())
			->setType(Hydrators\Channels\Channel::class);

		/**
		 * HELPERS
		 */

		$builder->addDefinition($this->prefix('helpers.connector'), new NetteDI\Definitions\ServiceDefinition())
			->setType(Helpers\Connector::class);

		$builder->addDefinition($this->prefix('helpers.device'), new NetteDI\Definitions\ServiceDefinition())
			->setType(Helpers\Device::class);

		$builder->addDefinition($this->prefix('helpers.channel'), new NetteDI\Definitions\ServiceDefinition())
			->setType(Helpers\Channel::class);

		$builder->addDefinition($this->prefix('helpers.messageBuilder'), new NetteDI\Definitions\ServiceDefinition())
			->setType(Helpers\MessageBuilder::class);

		/**
		 * COMMANDS
		 */

		$builder->addDefinition($this->prefix('commands.execute'), new NetteDI\Definitions\ServiceDefinition())
			->setType(Commands\Execute::class);

		$builder->addDefinition($this->prefix('commands.install'), new NetteDI\Definitions\ServiceDefinition())
			->setType(Commands\Install::class)
			->setArguments([
				'logger' => $logger,
			]);

		/**
		 * CONNECTOR
		 */

		$builder->addFactoryDefinition($this->prefix('executor.factory'))
			->setImplement(Connector\ConnectorFactory::class)
			->addTag(
				DevicesDI\DevicesExtension::CONNECTOR_TYPE_TAG,
				Entities\Connectors\Connector::TYPE,
			)
			->getResultDefinition()
			->setType(Connector\Connector::class)
			->setArguments([
				'clientsFactories' => $builder->findByType(Clients\ClientFactory::class),
				'writersFactories' => $builder->findByType(Writers\WriterFactory::class),
				'logger' => $logger,
			]);
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
			'FastyBird\Connector\Modbus\Entities',
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
						'FastyBird\Connector\Modbus\Documents',
					]);
				}
			}
		}

		/**
		 * DOCTRINE FIXTURES
		 */

		$fixturesLoaderService = $builder->getDefinitionByType(NettrineFixtures\Loader\FixturesLoader::class);

		if ($fixturesLoaderService instanceof NetteDI\Definitions\ServiceDefinition) {
			//$fixturesLoaderService->addSetup('addFixture', [new Fixtures\Connector()]);
			//$fixturesLoaderService->addSetup('addFixture', [new Fixtures\ConnectorProperties()]);
			//$fixturesLoaderService->addSetup('addFixture', [new Fixtures\Devices()]);
			//$fixturesLoaderService->addSetup('addFixture', [new Fixtures\DevicesProperties()]);
			//$fixturesLoaderService->addSetup('addFixture', [new Fixtures\Channels()]);
			//$fixturesLoaderService->addSetup('addFixture', [new Fixtures\ChannelsProperties()]);
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
