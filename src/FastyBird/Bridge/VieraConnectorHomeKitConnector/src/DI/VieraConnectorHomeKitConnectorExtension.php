<?php declare(strict_types = 1);

/**
 * VieraConnectorHomeKitConnectorExtension.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:VieraConnectorHomeKitConnectorBridge!
 * @subpackage     DI
 * @since          1.0.0
 *
 * @date           24.08.24
 */

namespace FastyBird\Bridge\VieraConnectorHomeKitConnector\DI;

use Contributte\Translation;
use FastyBird\Bridge\VieraConnectorHomeKitConnector;
use FastyBird\Bridge\VieraConnectorHomeKitConnector\Builders;
use FastyBird\Bridge\VieraConnectorHomeKitConnector\Commands;
use FastyBird\Bridge\VieraConnectorHomeKitConnector\Controllers;
use FastyBird\Bridge\VieraConnectorHomeKitConnector\Hydrators;
use FastyBird\Bridge\VieraConnectorHomeKitConnector\Mapping;
use FastyBird\Bridge\VieraConnectorHomeKitConnector\Protocol;
use FastyBird\Bridge\VieraConnectorHomeKitConnector\Router;
use FastyBird\Bridge\VieraConnectorHomeKitConnector\Schemas;
use FastyBird\Core\Boot as ApplicationBoot;
use FastyBird\Core\DI as CoreDI;
use FastyBird\Core\Documents;
use FastyBird\Core\Http\Routing;
use Nette\Bootstrap;
use Nette\DI;
use Nette\Schema;
use Nettrine\ORM as NettrineORM;
use stdClass;
use function array_keys;
use function array_pop;
use function assert;
use const DIRECTORY_SEPARATOR;

/**
 * Viera devices to HomeKit connector bridge extension
 *
 * @package        FastyBird:VieraConnectorHomeKitConnectorBridge!
 * @subpackage     DI
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class VieraConnectorHomeKitConnectorExtension extends DI\CompilerExtension implements Translation\DI\TranslationProviderInterface
{

	public const NAME = 'fbVieraConnectorHomeKitConnectorBridge';

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

		$logger = $builder->addDefinition($this->prefix('logger'), new DI\Definitions\ServiceDefinition())
			->setType(VieraConnectorHomeKitConnector\Logger::class)
			->setAutowired(false);

		/**
		 * BUILDERS
		 */

		$builder->addDefinition($this->prefix('builder'), new DI\Definitions\ServiceDefinition())
			->setType(Builders\Builder::class)
			->setArguments([
				'logger' => $logger,
			]);

		/**
		 * MAPPING CONFIGURATION
		 */

		$builder->addDefinition($this->prefix('mapping.builder'), new DI\Definitions\ServiceDefinition())
			->setType(Mapping\Builder::class);

		/**
		 * ROUTE MIDDLEWARES & ROUTING
		 */

		$builder->addDefinition($this->prefix('router.api.routes'), new DI\Definitions\ServiceDefinition())
			->setType(Router\ApiRoutes::class)
			->setArguments(['usePrefix' => $configuration->apiPrefix]);

		/**
		 * API CONTROLLERS
		 */

		$builder->addDefinition(
			$this->prefix('controllers.bridges'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Controllers\BridgesV1::class)
			->addSetup('setLogger', [$logger])
			->addTag('nette.inject');

		/**
		 * JSON-API SCHEMAS
		 */

		$builder->addDefinition($this->prefix('schemas.device.viera'), new DI\Definitions\ServiceDefinition())
			->setType(Schemas\Devices\Viera::class);

		$builder->addDefinition($this->prefix('schemas.channel.television'), new DI\Definitions\ServiceDefinition())
			->setType(Schemas\Channels\Television::class);

		$builder->addDefinition(
			$this->prefix('schemas.channel.televisionSpeaker'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Schemas\Channels\TelevisionSpeaker::class);

		$builder->addDefinition($this->prefix('schemas.channel.inputSource'), new DI\Definitions\ServiceDefinition())
			->setType(Schemas\Channels\InputSource::class);

		/**
		 * JSON-API HYDRATORS
		 */

		$builder->addDefinition($this->prefix('hydrators.device.viera'), new DI\Definitions\ServiceDefinition())
			->setType(Hydrators\Devices\Viera::class);

		$builder->addDefinition($this->prefix('hydrators.channel.television'), new DI\Definitions\ServiceDefinition())
			->setType(Hydrators\Channels\Television::class);

		$builder->addDefinition(
			$this->prefix('hydrators.channel.televisionSpeaker'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Hydrators\Channels\TelevisionSpeaker::class);

		$builder->addDefinition($this->prefix('hydrators.channel.inputSource'), new DI\Definitions\ServiceDefinition())
			->setType(Hydrators\Channels\InputSource::class);

		/**
		 * HOMEKIT PROTOCOL
		 */

		$builder->addDefinition($this->prefix('protocol.accessory.factory.viera'))
			->setType(Protocol\Accessories\VieraFactory::class);

		$builder->addDefinition($this->prefix('protocol.service.factory.television'))
			->setType(Protocol\Services\TelevisionFactory::class);

		$builder->addDefinition($this->prefix('protocol.service.factory.televisionSpeaker'))
			->setType(Protocol\Services\TelevisionSpeakerFactory::class);

		$builder->addDefinition($this->prefix('protocol.service.factory.inputSource'))
			->setType(Protocol\Services\InputSourceFactory::class);

		/**
		 * COMMANDS
		 */

		$builder->addDefinition($this->prefix('commands.build'), new DI\Definitions\ServiceDefinition())
			->setType(Commands\Build::class)
			->setArguments([
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
			'FastyBird\Bridge\VieraConnectorHomeKitConnector\Entities',
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
						'FastyBird\Bridge\VieraConnectorHomeKitConnector\Documents',
					]);
				}
			}
		}

		/**
		 * API ROUTER
		 */

		$routerService = $builder->getDefinitionByType(Routing\Router::class);

		if ($routerService instanceof DI\Definitions\ServiceDefinition) {
			$routerService->addSetup('?->registerRoutes(?)', [
				$builder->getDefinitionByType(Router\ApiRoutes::class),
				$routerService,
			]);
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
