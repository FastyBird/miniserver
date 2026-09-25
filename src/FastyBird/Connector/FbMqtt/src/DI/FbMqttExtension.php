<?php declare(strict_types = 1);

/**
 * FbMqttExtension.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:FbMqttConnector!
 * @subpackage     DI
 * @since          1.0.0
 *
 * @date           03.12.20
 */

namespace FastyBird\Connector\FbMqtt\DI;

use Contributte\Translation;
use FastyBird\Connector\FbMqtt;
use FastyBird\Connector\FbMqtt\API;
use FastyBird\Connector\FbMqtt\Clients;
use FastyBird\Connector\FbMqtt\Commands;
use FastyBird\Connector\FbMqtt\Connector;
use FastyBird\Connector\FbMqtt\Entities;
use FastyBird\Connector\FbMqtt\Helpers;
use FastyBird\Connector\FbMqtt\Hydrators;
use FastyBird\Connector\FbMqtt\Queue;
use FastyBird\Connector\FbMqtt\Schemas;
use FastyBird\Connector\FbMqtt\Subscribers;
use FastyBird\Connector\FbMqtt\Writers;
use FastyBird\Core\Boot;
use FastyBird\Core\DI as CoreDI;
use FastyBird\Core\Documents;
use FastyBird\Module\Devices\DI as DevicesDI;
use Nette\Bootstrap;
use Nette\DI;
use Nettrine\ORM as NettrineORM;
use function array_keys;
use function array_pop;
use const DIRECTORY_SEPARATOR;

/**
 * FastyBird MQTT connector
 *
 * @package        FastyBird:FbMqttConnector!
 * @subpackage     DI
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class FbMqttExtension extends DI\CompilerExtension implements Translation\DI\TranslationProviderInterface
{

	public const NAME = 'fbFbMqttConnector';

	public static function register(
		Boot\Configurator $config,
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

	/**
	 * @throws DI\NotAllowedDuringResolvingException
	 */
	public function loadConfiguration(): void
	{
		$builder = $this->getContainerBuilder();

		$logger = $builder->addDefinition($this->prefix('logger'), new DI\Definitions\ServiceDefinition())
			->setType(FbMqtt\Logger::class)
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
			->addTag(CoreDI\CoreExtension::CONSUMER_STATE, false);

		/**
		 * CLIENTS
		 */

		$builder->addFactoryDefinition($this->prefix('client.apiv1'))
			->setImplement(Clients\FbMqttV1Factory::class)
			->getResultDefinition()
			->setType(Clients\FbMqttV1::class)
			->setArguments([
				'logger' => $logger,
			]);

		/**
		 * API
		 */

		$builder->addDefinition($this->prefix('api.connectionsManager'), new DI\Definitions\ServiceDefinition())
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
			$this->prefix('queue.consumers.store.device'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Queue\Consumers\DeviceAttribute::class)
			->setArguments([
				'logger' => $logger,
			]);

		$builder->addDefinition(
			$this->prefix('queue.consumers.store.deviceProperty'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Queue\Consumers\DeviceProperty::class)
			->setArguments([
				'logger' => $logger,
			]);

		$builder->addDefinition(
			$this->prefix('queue.consumers.store.extension'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Queue\Consumers\ExtensionAttribute::class)
			->setArguments([
				'logger' => $logger,
			]);

		$builder->addDefinition(
			$this->prefix('queue.consumers.store.channel'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Queue\Consumers\ChannelAttribute::class)
			->setArguments([
				'logger' => $logger,
			]);

		$builder->addDefinition(
			$this->prefix('queue.consumers.store.channelProperty'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Queue\Consumers\ChannelProperty::class)
			->setArguments([
				'logger' => $logger,
			]);

		$builder->addDefinition(
			$this->prefix('queue.consumers.store.writeV1DevicePropertyState'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Queue\Consumers\WriteV1DevicePropertyState::class)
			->setArguments([
				'logger' => $logger,
			]);

		$builder->addDefinition(
			$this->prefix('queue.consumers.store.writeV1ChannelPropertyState'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Queue\Consumers\WriteV1ChannelPropertyState::class)
			->setArguments([
				'logger' => $logger,
			]);

		$builder->addDefinition(
			$this->prefix('queue.consumers'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Queue\Consumers::class)
			->setArguments([
				'consumers' => $builder->findByType(Queue\Consumer::class),
				'logger' => $logger,
			]);

		$builder->addDefinition(
			$this->prefix('queue.queue'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Queue\Queue::class)
			->setArguments([
				'logger' => $logger,
			]);

		$builder->addDefinition(
			$this->prefix('queue.messageBuilder'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Helpers\MessageBuilder::class);

		/**
		 * SUBSCRIBERS
		 */

		$builder->addDefinition($this->prefix('subscribers.controls'), new DI\Definitions\ServiceDefinition())
			->setType(Subscribers\Controls::class);

		/**
		 * JSON-API SCHEMAS
		 */

		$builder->addDefinition($this->prefix('schemas.connector.fbMqtt'), new DI\Definitions\ServiceDefinition())
			->setType(Schemas\Connectors\Connector::class);

		$builder->addDefinition($this->prefix('schemas.device.fbMqtt'), new DI\Definitions\ServiceDefinition())
			->setType(Schemas\Devices\Device::class);

		$builder->addDefinition($this->prefix('schemas.channel.fbMqtt'), new DI\Definitions\ServiceDefinition())
			->setType(Schemas\Channels\Channel::class);

		/**
		 * JSON-API HYDRATORS
		 */

		$builder->addDefinition($this->prefix('hydrators.connector.fbMqtt'), new DI\Definitions\ServiceDefinition())
			->setType(Hydrators\Connectors\Connector::class);

		$builder->addDefinition($this->prefix('hydrators.device.fbMqtt'), new DI\Definitions\ServiceDefinition())
			->setType(Hydrators\Devices\Device::class);

		$builder->addDefinition($this->prefix('hydrators.channel.fbMqtt'), new DI\Definitions\ServiceDefinition())
			->setType(Hydrators\Channels\Channel::class);

		/**
		 * HELPERS
		 */

		$builder->addDefinition($this->prefix('helpers.connector'), new DI\Definitions\ServiceDefinition())
			->setType(Helpers\Connector::class);

		/**
		 * COMMANDS
		 */

		$builder->addDefinition($this->prefix('commands.execute'), new DI\Definitions\ServiceDefinition())
			->setType(Commands\Execute::class);

		$builder->addDefinition($this->prefix('commands.install'), new DI\Definitions\ServiceDefinition())
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
	 * @throws DI\MissingServiceException
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
			'FastyBird\Connector\FbMqtt\Entities',
			__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'Entities',
		);

		/**
		 * APPLICATION DOCUMENTS
		 */

		$services = $builder->findByTag(CoreDI\CoreExtension::DRIVER_TAG);

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
					Documents\Mapping\Driver\MappingDriverChain::class,
				);

				if ($documentAttributeDriverChainService instanceof DI\Definitions\ServiceDefinition) {
					$documentAttributeDriverChainService->addSetup('addDriver', [
						$documentAttributeDriverService,
						'FastyBird\Connector\FbMqtt\Documents',
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
