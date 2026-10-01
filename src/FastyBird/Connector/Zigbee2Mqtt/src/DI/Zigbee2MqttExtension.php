<?php declare(strict_types = 1);

/**
 * Zigbee2MqttExtension.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:Zigbee2MqttConnector!
 * @subpackage     DI
 * @since          1.0.0
 *
 * @date           23.12.23
 */

namespace FastyBird\Connector\Zigbee2Mqtt\DI;

use Contributte\Translation;
use FastyBird\Connector\Zigbee2Mqtt;
use FastyBird\Connector\Zigbee2Mqtt\API;
use FastyBird\Connector\Zigbee2Mqtt\Clients;
use FastyBird\Connector\Zigbee2Mqtt\Commands;
use FastyBird\Connector\Zigbee2Mqtt\Connector;
use FastyBird\Connector\Zigbee2Mqtt\Entities;
use FastyBird\Connector\Zigbee2Mqtt\Helpers;
use FastyBird\Connector\Zigbee2Mqtt\Hydrators;
use FastyBird\Connector\Zigbee2Mqtt\Models;
use FastyBird\Connector\Zigbee2Mqtt\Queue;
use FastyBird\Connector\Zigbee2Mqtt\Schemas;
use FastyBird\Connector\Zigbee2Mqtt\Subscribers;
use FastyBird\Connector\Zigbee2Mqtt\Writers;
use FastyBird\Core\Boot;
use FastyBird\Core\Documents;
use FastyBird\Core\Documents\DI as DocumentsDI;
use FastyBird\Core\Exchange\DI as ExchangeDI;
use FastyBird\Module\Devices\DI as DevicesDI;
use Nette\Bootstrap;
use Nette\DI as NetteDI;
use Nettrine\ORM as NettrineORM;
use function array_keys;
use function array_pop;
use const DIRECTORY_SEPARATOR;

/**
 * Zigbee2MQTT connector
 *
 * @package        FastyBird:Zigbee2MqttConnector!
 * @subpackage     DI
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class Zigbee2MqttExtension extends NetteDI\CompilerExtension implements Translation\DI\TranslationProviderInterface
{

	public const NAME = 'fbZigbee2MqttConnector';

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
			->setType(Zigbee2Mqtt\Logger::class)
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

		$builder->addFactoryDefinition($this->prefix('clients.mqtt'))
			->setImplement(Clients\MqttFactory::class)
			->getResultDefinition()
			->setType(Clients\Mqtt::class)
			->setArguments([
				'logger' => $logger,
			]);

		$builder->addFactoryDefinition($this->prefix('clients.discovery'))
			->setImplement(Clients\DiscoveryFactory::class)
			->getResultDefinition()
			->setType(Clients\Discovery::class)
			->setArguments([
				'logger' => $logger,
			]);

		$builder->addFactoryDefinition($this->prefix('clients.subscriber.bridge'))
			->setImplement(Clients\Subscribers\BridgeFactory::class)
			->getResultDefinition()
			->setType(Clients\Subscribers\Bridge::class)
			->setArguments([
				'logger' => $logger,
			]);

		$builder->addFactoryDefinition($this->prefix('clients.subscriber.device'))
			->setImplement(Clients\Subscribers\DeviceFactory::class)
			->getResultDefinition()
			->setType(Clients\Subscribers\Device::class)
			->setArguments([
				'logger' => $logger,
			]);

		/**
		 * API
		 */

		$builder->addDefinition($this->prefix('api.connectionsManager'), new NetteDI\Definitions\ServiceDefinition())
			->setType(API\ConnectionManager::class);

		$builder->addFactoryDefinition($this->prefix('api.client'))
			->setImplement(API\ClientFactory::class)
			->getResultDefinition()
			->setType(API\Client::class)
			->setArguments([
				'logger' => $logger,
			]);

		/**
		 * MESSAGES QUEUE
		 */

		$builder->addDefinition(
			$this->prefix('queue.consumers.store.bridgeConnectionState'),
			new NetteDI\Definitions\ServiceDefinition(),
		)
			->setType(Queue\Consumers\StoreBridgeConnectionState::class)
			->setArguments([
				'logger' => $logger,
			]);

		$builder->addDefinition(
			$this->prefix('queue.consumers.store.bridgeDevices'),
			new NetteDI\Definitions\ServiceDefinition(),
		)
			->setType(Queue\Consumers\StoreBridgeDevices::class)
			->setArguments([
				'logger' => $logger,
			]);

		$builder->addDefinition(
			$this->prefix('queue.consumers.store.bridgeEvent'),
			new NetteDI\Definitions\ServiceDefinition(),
		)
			->setType(Queue\Consumers\StoreBridgeEvent::class)
			->setArguments([
				'logger' => $logger,
			]);

		$builder->addDefinition(
			$this->prefix('queue.consumers.store.bridgeGroups'),
			new NetteDI\Definitions\ServiceDefinition(),
		)
			->setType(Queue\Consumers\StoreBridgeGroups::class)
			->setArguments([
				'logger' => $logger,
			]);

		$builder->addDefinition(
			$this->prefix('queue.consumers.store.bridgeInfo'),
			new NetteDI\Definitions\ServiceDefinition(),
		)
			->setType(Queue\Consumers\StoreBridgeInfo::class)
			->setArguments([
				'logger' => $logger,
			]);

		$builder->addDefinition(
			$this->prefix('queue.consumers.store.bridgeLog'),
			new NetteDI\Definitions\ServiceDefinition(),
		)
			->setType(Queue\Consumers\StoreBridgeLog::class)
			->setArguments([
				'logger' => $logger,
			]);

		$builder->addDefinition(
			$this->prefix('queue.consumers.store.deviceConnectionState'),
			new NetteDI\Definitions\ServiceDefinition(),
		)
			->setType(Queue\Consumers\StoreDeviceConnectionState::class)
			->setArguments([
				'logger' => $logger,
			]);

		$builder->addDefinition(
			$this->prefix('queue.consumers.store.deviceState'),
			new NetteDI\Definitions\ServiceDefinition(),
		)
			->setType(Queue\Consumers\StoreDeviceState::class)
			->setArguments([
				'logger' => $logger,
			]);

		$builder->addDefinition(
			$this->prefix('queue.consumers.write.subDeviceChannelPropertyState'),
			new NetteDI\Definitions\ServiceDefinition(),
		)
			->setType(Queue\Consumers\WriteSubDeviceChannelPropertyState::class)
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

		$builder->addDefinition(
			$this->prefix('schemas.connector'),
			new NetteDI\Definitions\ServiceDefinition(),
		)
			->setType(Schemas\Connectors\Connector::class);

		$builder->addDefinition(
			$this->prefix('schemas.device.bridge'),
			new NetteDI\Definitions\ServiceDefinition(),
		)
			->setType(Schemas\Devices\Bridge::class);

		$builder->addDefinition(
			$this->prefix('schemas.device.subDevice'),
			new NetteDI\Definitions\ServiceDefinition(),
		)
			->setType(Schemas\Devices\SubDevice::class);

		$builder->addDefinition(
			$this->prefix('schemas.channel'),
			new NetteDI\Definitions\ServiceDefinition(),
		)
			->setType(Schemas\Channels\Channel::class);

		/**
		 * JSON-API HYDRATORS
		 */

		$builder->addDefinition(
			$this->prefix('hydrators.connector'),
			new NetteDI\Definitions\ServiceDefinition(),
		)
			->setType(Hydrators\Connectors\Connector::class);

		$builder->addDefinition(
			$this->prefix('hydrators.device.bridge'),
			new NetteDI\Definitions\ServiceDefinition(),
		)
			->setType(Hydrators\Devices\Bridge::class);

		$builder->addDefinition(
			$this->prefix('hydrators.device.subDevice'),
			new NetteDI\Definitions\ServiceDefinition(),
		)
			->setType(Hydrators\Devices\SubDevice::class);

		$builder->addDefinition(
			$this->prefix('hydrators.channel'),
			new NetteDI\Definitions\ServiceDefinition(),
		)
			->setType(Hydrators\Channels\Channel::class);

		/**
		 * MODELS
		 */

		$builder->addDefinition($this->prefix('models.stateRepository'), new NetteDI\Definitions\ServiceDefinition())
			->setType(Models\StateRepository::class);

		/**
		 * HELPERS
		 */

		$builder->addDefinition($this->prefix('helpers.messageBuilder'), new NetteDI\Definitions\ServiceDefinition())
			->setType(Helpers\MessageBuilder::class);

		$builder->addDefinition($this->prefix('helpers.connector'), new NetteDI\Definitions\ServiceDefinition())
			->setType(Helpers\Connectors\Connector::class);

		$builder->addDefinition($this->prefix('helpers.devices.bridge'), new NetteDI\Definitions\ServiceDefinition())
			->setType(Helpers\Devices\Bridge::class);

		$builder->addDefinition($this->prefix('helpers.devices.subDevice'), new NetteDI\Definitions\ServiceDefinition())
			->setType(Helpers\Devices\SubDevice::class);

		/**
		 * COMMANDS
		 */

		$builder->addDefinition($this->prefix('commands.execute'), new NetteDI\Definitions\ServiceDefinition())
			->setType(Commands\Execute::class);

		$builder->addDefinition($this->prefix('commands.discover'), new NetteDI\Definitions\ServiceDefinition())
			->setType(Commands\Discover::class);

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
			'FastyBird\Connector\Zigbee2Mqtt\Entities',
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
						'FastyBird\Connector\Zigbee2Mqtt\Documents',
					]);
				}
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
