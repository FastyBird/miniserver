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

use FastyBird\Bridge\DevicesModuleUiModule;
use FastyBird\Bridge\DevicesModuleUiModule\Consumers as DevicesModuleUiModuleConsumers;
use FastyBird\Bridge\DevicesModuleUiModule\Hydrators;
use FastyBird\Bridge\DevicesModuleUiModule\Schemas;
use FastyBird\Bridge\DevicesModuleUiModule\Subscribers;
use FastyBird\Core\Boot;
use FastyBird\Core\DI as CoreDI;
use FastyBird\Core\Documents;
use FastyBird\Core\Exchange\Consumers as ExchangeConsumers;
use FastyBird\Core\Http\Routing;
use FastyBird\Core\WebSockets\Server;
use FastyBird\Core\WebSockets\Topics;
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
 * Redis DB devices module bridge extension
 *
 * @package        FastyBird:DevicesModuleUiModuleBridge!
 * @subpackage     DI
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class DevicesModuleUiModuleExtension extends NetteDI\CompilerExtension
{

	public const NAME = 'fbDevicesModuleUiModuleBridge';

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
			'database' => Schema\Expect::int(0),
		]);
	}

	/**
	 * @throws NetteDI\NotAllowedDuringResolvingException
	 */
	public function loadConfiguration(): void
	{
		$builder = $this->getContainerBuilder();
		$configuration = $this->getConfig();
		assert($configuration instanceof stdClass);

		$logger = $builder->addDefinition($this->prefix('logger'), new NetteDI\Definitions\ServiceDefinition())
			->setType(DevicesModuleUiModule\Logger::class)
			->setAutowired(false);

		/**
		 * SUBSCRIBERS
		 */

		$builder->addDefinition($this->prefix('subscribers.moduleEntities'), new NetteDI\Definitions\ServiceDefinition())
			->setType(Subscribers\ModuleEntities::class);

		$builder->addDefinition($this->prefix('subscribers.stateEntities'), new NetteDI\Definitions\ServiceDefinition())
			->setType(Subscribers\StateEntities::class);

		$builder->addDefinition($this->prefix('subscribers.documentsMapper'), new NetteDI\Definitions\ServiceDefinition())
			->setType(Subscribers\DocumentsMapper::class);

		$builder->addDefinition($this->prefix('subscribers.dataSourceAction'), new NetteDI\Definitions\ServiceDefinition())
			->setType(Subscribers\ActionCommand::class);

		/**
		 * JSON-API SCHEMAS
		 */

		$builder->addDefinition(
			$this->prefix('schemas.dataSources.connectorProperty'),
			new NetteDI\Definitions\ServiceDefinition(),
		)
			->setType(Schemas\Widgets\DataSources\ConnectorProperty::class);

		$builder->addDefinition(
			$this->prefix('schemas.dataSources.deviceProperty'),
			new NetteDI\Definitions\ServiceDefinition(),
		)
			->setType(Schemas\Widgets\DataSources\DeviceProperty::class);

		$builder->addDefinition(
			$this->prefix('schemas.dataSources.channelProperty'),
			new NetteDI\Definitions\ServiceDefinition(),
		)
			->setType(Schemas\Widgets\DataSources\ChannelProperty::class);

		/**
		 * JSON-API HYDRATORS
		 */

		$builder->addDefinition(
			$this->prefix('hydrators.dataSources.connectorProperty'),
			new NetteDI\Definitions\ServiceDefinition(),
		)
			->setType(Hydrators\Widgets\DataSources\ConnectorProperty::class);

		$builder->addDefinition(
			$this->prefix('hydrators.dataSources.deviceProperty'),
			new NetteDI\Definitions\ServiceDefinition(),
		)
			->setType(Hydrators\Widgets\DataSources\DeviceProperty::class);

		$builder->addDefinition(
			$this->prefix('hydrators.dataSources.channelProperty'),
			new NetteDI\Definitions\ServiceDefinition(),
		)
			->setType(Hydrators\Widgets\DataSources\ChannelProperty::class);

		/**
		 * COMMUNICATION EXCHANGE
		 */

		if (
			$builder->findByType(Routing\LinkGenerator::class) !== []
			&& $builder->findByType(Topics\IStorage::class) !== []
		) {
			$builder->addDefinition(
				$this->prefix('exchange.consumer.stateEntities'),
				new NetteDI\Definitions\ServiceDefinition(),
			)
				->setType(DevicesModuleUiModuleConsumers\SocketsBridge::class)
				->setArguments([
					'logger' => $logger,
				])
				->addTag(CoreDI\CoreExtension::CONSUMER_STATE, false);
		}
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
			'FastyBird\Bridge\DevicesModuleUiModule\Entities',
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
						'FastyBird\Bridge\DevicesModuleUiModule\Documents',
					]);
				}
			}
		}

		/**
		 * WEBSOCKETS
		 */

		try {
			$consumerService = $builder->getDefinitionByType(ExchangeConsumers\Container::class);
			assert($consumerService instanceof NetteDI\Definitions\ServiceDefinition);

			$wsServerService = $builder->getDefinitionByType(Server\ServerRuntime::class);
			assert($wsServerService instanceof NetteDI\Definitions\ServiceDefinition);

			$wsServerService->addSetup(
				'?->onCreate[] = function() {?->enable(?);}',
				[
					'@self',
					$consumerService,
					DevicesModuleUiModuleConsumers\SocketsBridge::class,
				],
			);

		} catch (NetteDI\MissingServiceException) {
			// Extension is not registered
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
