<?php declare(strict_types = 1);

/**
 * DevicesModuleUiModuleExtension.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModuleUiModuleBridge!
 * @subpackage     DI
 * @since          1.0.0
 *
 * @date           04.08.24
 */

namespace FastyBird\Bridge\DevicesModuleUiModule\DI;

use Doctrine\Persistence;
use FastyBird\Bridge\DevicesModuleUiModule;
use FastyBird\Bridge\DevicesModuleUiModule\Consumers;
use FastyBird\Bridge\DevicesModuleUiModule\Hydrators;
use FastyBird\Bridge\DevicesModuleUiModule\Schemas;
use FastyBird\Bridge\DevicesModuleUiModule\Subscribers;
use FastyBird\Core\Application\Boot as ApplicationBoot;
use FastyBird\Core\Application\DI as ApplicationDI;
use FastyBird\Core\Application\Documents as ApplicationDocuments;
use FastyBird\Core\Exchange\Consumers as ExchangeConsumers;
use FastyBird\Core\Exchange\DI as ExchangeDI;
use Nette\Bootstrap;
use Nette\DI;
use Nette\Schema;
use Nettrine\ORM as NettrineORM;
use stdClass;
use function array_keys;
use function array_pop;
use function assert;
use function class_exists;
use const DIRECTORY_SEPARATOR;

/**
 * Redis DB devices module bridge extension
 *
 * @package        FastyBird:DevicesModuleUiModuleBridge!
 * @subpackage     DI
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class DevicesModuleUiModuleExtension extends DI\CompilerExtension
{

	public const NAME = 'fbDevicesModuleUiModuleBridge';

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
			'database' => Schema\Expect::int(0),
		]);
	}

	/**
	 * @throws DI\NotAllowedDuringResolvingException
	 */
	public function loadConfiguration(): void
	{
		$builder = $this->getContainerBuilder();
		$configuration = $this->getConfig();
		assert($configuration instanceof stdClass);

		$logger = $builder->addDefinition($this->prefix('logger'), new DI\Definitions\ServiceDefinition())
			->setType(DevicesModuleUiModule\Logger::class)
			->setAutowired(false);

		/**
		 * SUBSCRIBERS
		 */

		$builder->addDefinition($this->prefix('subscribers.moduleEntities'), new DI\Definitions\ServiceDefinition())
			->setType(Subscribers\ModuleEntities::class);

		$builder->addDefinition($this->prefix('subscribers.stateEntities'), new DI\Definitions\ServiceDefinition())
			->setType(Subscribers\StateEntities::class);

		$builder->addDefinition($this->prefix('subscribers.documentsMapper'), new DI\Definitions\ServiceDefinition())
			->setType(Subscribers\DocumentsMapper::class);

		$builder->addDefinition($this->prefix('subscribers.dataSourceAction'), new DI\Definitions\ServiceDefinition())
			->setType(Subscribers\ActionCommand::class);

		/**
		 * JSON-API SCHEMAS
		 */

		$builder->addDefinition(
			$this->prefix('schemas.dataSources.connectorProperty'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Schemas\Widgets\DataSources\ConnectorProperty::class);

		$builder->addDefinition(
			$this->prefix('schemas.dataSources.deviceProperty'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Schemas\Widgets\DataSources\DeviceProperty::class);

		$builder->addDefinition(
			$this->prefix('schemas.dataSources.channelProperty'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Schemas\Widgets\DataSources\ChannelProperty::class);

		/**
		 * JSON-API HYDRATORS
		 */

		$builder->addDefinition(
			$this->prefix('hydrators.dataSources.connectorProperty'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Hydrators\Widgets\DataSources\ConnectorProperty::class);

		$builder->addDefinition(
			$this->prefix('hydrators.dataSources.deviceProperty'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Hydrators\Widgets\DataSources\DeviceProperty::class);

		$builder->addDefinition(
			$this->prefix('hydrators.dataSources.channelProperty'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Hydrators\Widgets\DataSources\ChannelProperty::class);

		/**
		 * COMMUNICATION EXCHANGE
		 */

		if (
			$builder->findByType('IPub\WebSockets\Router\LinkGenerator') !== []
			&& $builder->findByType('IPub\WebSocketsWAMP\Topics\IStorage') !== []
		) {
			$builder->addDefinition(
				$this->prefix('exchange.consumer.stateEntities'),
				new DI\Definitions\ServiceDefinition(),
			)
				->setType(Consumers\SocketsBridge::class)
				->setArguments([
					'logger' => $logger,
				])
				->addTag(ExchangeDI\ExchangeExtension::CONSUMER_STATE, false);
		}
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
						'FastyBird\Bridge\DevicesModuleUiModule\Entities',
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
						'FastyBird\Bridge\DevicesModuleUiModule\Documents',
					]);
				}
			}
		}

		/**
		 * WEBSOCKETS
		 */

		if (class_exists('IPub\WebSockets\DI\WebSocketsExtension')) {
			try {
				$consumerService = $builder->getDefinitionByType(ExchangeConsumers\Container::class);
				assert($consumerService instanceof DI\Definitions\ServiceDefinition);

				$wsServerService = $builder->getDefinitionByType('IPub\WebSockets\Server\Server');
				assert($wsServerService instanceof DI\Definitions\ServiceDefinition);

				$wsServerService->addSetup(
					'?->onCreate[] = function() {?->enable(?);}',
					[
						'@self',
						$consumerService,
						Consumers\SocketsBridge::class,
					],
				);

			} catch (DI\MissingServiceException) {
				// Extension is not registered
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
