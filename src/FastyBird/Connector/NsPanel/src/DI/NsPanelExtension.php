<?php declare(strict_types = 1);

/**
 * NsPanelExtension.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     DI
 * @since          1.0.0
 *
 * @date           11.07.23
 */

namespace FastyBird\Connector\NsPanel\DI;

use Contributte\Translation;
use Doctrine\Persistence;
use FastyBird\Connector\NsPanel;
use FastyBird\Connector\NsPanel\API;
use FastyBird\Connector\NsPanel\Clients;
use FastyBird\Connector\NsPanel\Commands;
use FastyBird\Connector\NsPanel\Connector;
use FastyBird\Connector\NsPanel\Controllers;
use FastyBird\Connector\NsPanel\Entities;
use FastyBird\Connector\NsPanel\Helpers;
use FastyBird\Connector\NsPanel\Hydrators;
use FastyBird\Connector\NsPanel\Mapping;
use FastyBird\Connector\NsPanel\Middleware;
use FastyBird\Connector\NsPanel\Protocol;
use FastyBird\Connector\NsPanel\Queue;
use FastyBird\Connector\NsPanel\Router;
use FastyBird\Connector\NsPanel\Schemas;
use FastyBird\Connector\NsPanel\Servers;
use FastyBird\Connector\NsPanel\Services;
use FastyBird\Connector\NsPanel\Subscribers;
use FastyBird\Connector\NsPanel\Writers;
use FastyBird\Core\Application\Boot as ApplicationBoot;
use FastyBird\Core\Application\DI as ApplicationDI;
use FastyBird\Core\Application\Documents as ApplicationDocuments;
use FastyBird\Core\Exchange\DI as ExchangeDI;
use FastyBird\Module\Devices\DI as DevicesDI;
use Nette\Bootstrap;
use Nette\DI;
use Nettrine\ORM as NettrineORM;
use function array_keys;
use function array_pop;
use function assert;
use const DIRECTORY_SEPARATOR;

/**
 * NS Panel connector
 *
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     DI
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class NsPanelExtension extends DI\CompilerExtension implements Translation\DI\TranslationProviderInterface
{

	public const NAME = 'fbNsPanelConnector';

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

	/**
	 * @throws DI\NotAllowedDuringResolvingException
	 */
	public function loadConfiguration(): void
	{
		$builder = $this->getContainerBuilder();

		$logger = $builder->addDefinition($this->prefix('logger'), new DI\Definitions\ServiceDefinition())
			->setType(NsPanel\Logger::class)
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

		$builder->addFactoryDefinition($this->prefix('clients.gateway'))
			->setImplement(Clients\GatewayFactory::class)
			->getResultDefinition()
			->setType(Clients\Gateway::class)
			->setArguments([
				'logger' => $logger,
			]);

		$builder->addFactoryDefinition($this->prefix('clients.device'))
			->setImplement(Clients\DeviceFactory::class)
			->getResultDefinition()
			->setType(Clients\Device::class)
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

		/**
		 * SERVICES & FACTORIES
		 */

		$builder->addDefinition($this->prefix('services.httpClient'), new DI\Definitions\ServiceDefinition())
			->setType(Services\HttpClientFactory::class);

		/**
		 * API
		 */

		$builder->addFactoryDefinition($this->prefix('api.lanApi'))
			->setImplement(API\LanApiFactory::class)
			->getResultDefinition()
			->setType(API\LanApi::class)
			->setArguments([
				'logger' => $logger,
			]);

		/**
		 * MESSAGES QUEUE
		 */

		$builder->addDefinition(
			$this->prefix('queue.consumers.store.deviceState'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Queue\Consumers\StoreDeviceState::class)
			->setArguments([
				'logger' => $logger,
			]);

		$builder->addDefinition(
			$this->prefix('queue.consumers.store.deviceConnectionState'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Queue\Consumers\StoreDeviceConnectionState::class)
			->setArguments([
				'logger' => $logger,
			]);

		$builder->addDefinition(
			$this->prefix('queue.consumers.store.thirdPartyDevice'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Queue\Consumers\StoreThirdPartyDevice::class)
			->setArguments([
				'logger' => $logger,
			]);

		$builder->addDefinition(
			$this->prefix('queue.consumers.store.subDevice'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Queue\Consumers\StoreSubDevice::class)
			->setArguments([
				'logger' => $logger,
			]);

		$builder->addDefinition(
			$this->prefix('queue.consumers.write.subDeviceState'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Queue\Consumers\WriteSubDeviceState::class)
			->setArguments([
				'logger' => $logger,
			]);

		$builder->addDefinition(
			$this->prefix('queue.consumers.write.thirdPartyDeviceState'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Queue\Consumers\WriteThirdPartyDeviceState::class)
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

		/**
		 * SUBSCRIBERS
		 */

		$builder->addDefinition($this->prefix('subscribers.devices'), new DI\Definitions\ServiceDefinition())
			->setType(Subscribers\Devices::class);

		$builder->addDefinition($this->prefix('subscribers.properties'), new DI\Definitions\ServiceDefinition())
			->setType(Subscribers\Properties::class);

		$builder->addDefinition($this->prefix('subscribers.controls'), new DI\Definitions\ServiceDefinition())
			->setType(Subscribers\Controls::class);

		/**
		 * JSON-API SCHEMAS
		 */

		$builder->addDefinition(
			$this->prefix('schemas.connector'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Schemas\Connectors\Connector::class);

		$builder->addDefinition(
			$this->prefix('schemas.device.gateway'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Schemas\Devices\Gateway::class);

		$builder->addDefinition(
			$this->prefix('schemas.device.subDevice'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Schemas\Devices\SubDevice::class);

		$builder->addDefinition(
			$this->prefix('schemas.device.thirdPartyDevice'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Schemas\Devices\ThirdPartyDevice::class);

		$builder->addDefinition(
			$this->prefix('schemas.channel.battery'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Schemas\Channels\Battery::class);

		$builder->addDefinition(
			$this->prefix('schemas.channel.brightness'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Schemas\Channels\Brightness::class);

		$builder->addDefinition(
			$this->prefix('schemas.channel.cameraStream'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Schemas\Channels\CameraStream::class);

		$builder->addDefinition(
			$this->prefix('schemas.channel.colorRgb'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Schemas\Channels\ColorRgb::class);

		$builder->addDefinition(
			$this->prefix('schemas.channel.colorTemperature'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Schemas\Channels\ColorTemperature::class);

		$builder->addDefinition(
			$this->prefix('schemas.channel.detect'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Schemas\Channels\Detect::class);

		$builder->addDefinition(
			$this->prefix('schemas.channel.fault'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Schemas\Channels\Fault::class);

		$builder->addDefinition(
			$this->prefix('schemas.channel.humidity'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Schemas\Channels\Humidity::class);

		$builder->addDefinition(
			$this->prefix('schemas.channel.illuminationLevel'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Schemas\Channels\IlluminationLevel::class);

		$builder->addDefinition(
			$this->prefix('schemas.channel.motorCalibration'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Schemas\Channels\MotorCalibration::class);

		$builder->addDefinition(
			$this->prefix('schemas.channel.motorControl'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Schemas\Channels\MotorControl::class);

		$builder->addDefinition(
			$this->prefix('schemas.channel.motorReverse'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Schemas\Channels\MotorReverse::class);

		$builder->addDefinition(
			$this->prefix('schemas.channel.percentage'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Schemas\Channels\Percentage::class);

		$builder->addDefinition(
			$this->prefix('schemas.channel.power'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Schemas\Channels\Power::class);

		$builder->addDefinition(
			$this->prefix('schemas.channel.press'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Schemas\Channels\Press::class);

		$builder->addDefinition(
			$this->prefix('schemas.channel.rssi'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Schemas\Channels\Rssi::class);

		$builder->addDefinition(
			$this->prefix('schemas.channel.startup'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Schemas\Channels\Startup::class);

		$builder->addDefinition(
			$this->prefix('schemas.channel.temperature'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Schemas\Channels\Temperature::class);

		$builder->addDefinition(
			$this->prefix('schemas.channel.thermostat'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Schemas\Channels\Thermostat::class);

		$builder->addDefinition(
			$this->prefix('schemas.channel.thermostatModeDetect'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Schemas\Channels\ThermostatModeDetect::class);

		$builder->addDefinition(
			$this->prefix('schemas.channel.thermostatTargetSetPoint'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Schemas\Channels\ThermostatTargetSetPoint::class);

		$builder->addDefinition(
			$this->prefix('schemas.channel.toggle'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Schemas\Channels\Toggle::class);

		/**
		 * JSON-API HYDRATORS
		 */

		$builder->addDefinition(
			$this->prefix('hydrators.connector'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Hydrators\Connectors\Connector::class);

		$builder->addDefinition(
			$this->prefix('hydrators.device.gateway'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Hydrators\Devices\Gateway::class);

		$builder->addDefinition(
			$this->prefix('hydrators.device.subDevice'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Hydrators\Devices\SubDevice::class);

		$builder->addDefinition(
			$this->prefix('hydrators.device.thirdPartyDevice'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Hydrators\Devices\ThirdPartyDevice::class);

		$builder->addDefinition(
			$this->prefix('hydrators.channel.battery'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Hydrators\Channels\Battery::class);

		$builder->addDefinition(
			$this->prefix('hydrators.channel.brightness'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Hydrators\Channels\Brightness::class);

		$builder->addDefinition(
			$this->prefix('hydrators.channel.cameraStream'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Hydrators\Channels\CameraStream::class);

		$builder->addDefinition(
			$this->prefix('hydrators.channel.colorRgb'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Hydrators\Channels\ColorRgb::class);

		$builder->addDefinition(
			$this->prefix('hydrators.channel.colorTemperature'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Hydrators\Channels\ColorTemperature::class);

		$builder->addDefinition(
			$this->prefix('hydrators.channel.detect'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Hydrators\Channels\Detect::class);

		$builder->addDefinition(
			$this->prefix('hydrators.channel.fault'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Hydrators\Channels\Fault::class);

		$builder->addDefinition(
			$this->prefix('hydrators.channel.humidity'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Hydrators\Channels\Humidity::class);

		$builder->addDefinition(
			$this->prefix('hydrators.channel.illuminationLevel'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Hydrators\Channels\IlluminationLevel::class);

		$builder->addDefinition(
			$this->prefix('hydrators.channel.motorCalibration'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Hydrators\Channels\MotorCalibration::class);

		$builder->addDefinition(
			$this->prefix('hydrators.channel.motorControl'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Hydrators\Channels\MotorControl::class);

		$builder->addDefinition(
			$this->prefix('hydrators.channel.motorReverse'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Hydrators\Channels\MotorReverse::class);

		$builder->addDefinition(
			$this->prefix('hydrators.channel.percentage'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Hydrators\Channels\Percentage::class);

		$builder->addDefinition(
			$this->prefix('hydrators.channel.power'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Hydrators\Channels\Power::class);

		$builder->addDefinition(
			$this->prefix('hydrators.channel.press'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Hydrators\Channels\Press::class);

		$builder->addDefinition(
			$this->prefix('hydrators.channel.rssi'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Hydrators\Channels\Rssi::class);

		$builder->addDefinition(
			$this->prefix('hydrators.channel.startup'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Hydrators\Channels\Startup::class);

		$builder->addDefinition(
			$this->prefix('hydrators.channel.temperature'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Hydrators\Channels\Temperature::class);

		$builder->addDefinition(
			$this->prefix('hydrators.channel.thermostat'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Hydrators\Channels\Thermostat::class);

		$builder->addDefinition(
			$this->prefix('hydrators.channel.thermostatModeDetect'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Hydrators\Channels\ThermostatModeDetect::class);

		$builder->addDefinition(
			$this->prefix('hydrators.channel.thermostatTargetSetPoint'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Hydrators\Channels\ThermostatTargetSetPoint::class);

		$builder->addDefinition(
			$this->prefix('hydrators.channel.toggle'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Hydrators\Channels\Toggle::class);

		/**
		 * HELPERS
		 */

		$builder->addDefinition($this->prefix('helpers.connector'), new DI\Definitions\ServiceDefinition())
			->setType(Helpers\Connectors\Connector::class);

		$builder->addDefinition($this->prefix('helpers.gatewayDevice'), new DI\Definitions\ServiceDefinition())
			->setType(Helpers\Devices\Gateway::class);

		$builder->addDefinition($this->prefix('helpers.thirdPartyDevice'), new DI\Definitions\ServiceDefinition())
			->setType(Helpers\Devices\ThirdPartyDevice::class);

		$builder->addDefinition($this->prefix('helpers.subDevice'), new DI\Definitions\ServiceDefinition())
			->setType(Helpers\Devices\SubDevice::class);

		$builder->addDefinition($this->prefix('helpers.channel'), new DI\Definitions\ServiceDefinition())
			->setType(Helpers\Channels\Channel::class);

		$builder->addDefinition($this->prefix('helpers.messageBuilder'), new DI\Definitions\ServiceDefinition())
			->setType(Helpers\MessageBuilder::class);

		/**
		 * SERVERS
		 */

		$builder->addFactoryDefinition($this->prefix('server.http'))
			->setImplement(Servers\HttpFactory::class)
			->getResultDefinition()
			->setType(Servers\Http::class)
			->setArguments([
				'logger' => $logger,
			]);

		$router = $builder->addDefinition($this->prefix('http.router'), new DI\Definitions\ServiceDefinition())
			->setType(Router\Router::class)
			->setAutowired(false);

		$builder->addDefinition($this->prefix('http.middlewares.router'), new DI\Definitions\ServiceDefinition())
			->setType(Middleware\Router::class)
			->setArguments([
				'router' => $router,
				'logger' => $logger,
			]);

		$builder->addDefinition($this->prefix('http.controllers.directive'), new DI\Definitions\ServiceDefinition())
			->setType(Controllers\DirectiveController::class)
			->addSetup('setLogger', [$logger])
			->addTag('nette.inject');

		/**
		 * METADATA MAPPING
		 */

		$builder->addDefinition($this->prefix('mapping.builder'), new DI\Definitions\ServiceDefinition())
			->setType(Mapping\Builder::class);

		/**
		 * COMMUNICATION PROTOCOL
		 */

		$builder->addDefinition($this->prefix('protocol.driver'), new DI\Definitions\ServiceDefinition())
			->setType(Protocol\Driver::class);

		$builder->addDefinition($this->prefix('protocol.loader'), new DI\Definitions\ServiceDefinition())
			->setType(Protocol\Loader::class)
			->setArguments([
				'logger' => $logger,
			]);

		// DEVICES

		$builder->addDefinition(
			$this->prefix('protocol.factory.device.subDevice'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Protocol\Devices\SubDeviceFactory::class);

		$builder->addDefinition(
			$this->prefix('protocol.factory.device.thirdParty'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Protocol\Devices\ThirdPartyDeviceFactory::class);

		// CAPABILITIES

		$builder->addDefinition(
			$this->prefix('protocol.factory.capability.battery'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Protocol\Capabilities\BatteryFactory::class);

		$builder->addDefinition(
			$this->prefix('protocol.factory.capability.brightness'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Protocol\Capabilities\BrightnessFactory::class);

		$builder->addDefinition(
			$this->prefix('protocol.factory.capability.cameraStream'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Protocol\Capabilities\CameraStreamFactory::class);

		$builder->addDefinition(
			$this->prefix('protocol.factory.capability.colorRgb'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Protocol\Capabilities\ColorRgbFactory::class);

		$builder->addDefinition(
			$this->prefix('protocol.factory.capability.colorTemperature'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Protocol\Capabilities\ColorTemperatureFactory::class);

		$builder->addDefinition(
			$this->prefix('protocol.factory.capability.detect'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Protocol\Capabilities\DetectFactory::class);

		$builder->addDefinition(
			$this->prefix('protocol.factory.capability.fault'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Protocol\Capabilities\FaultFactory::class);

		$builder->addDefinition(
			$this->prefix('protocol.factory.capability.humidity'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Protocol\Capabilities\HumidityFactory::class);

		$builder->addDefinition(
			$this->prefix('protocol.factory.capability.illuminationLevel'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Protocol\Capabilities\IlluminationLevelFactory::class);

		$builder->addDefinition(
			$this->prefix('protocol.factory.capability.motorCalibration'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Protocol\Capabilities\MotorCalibrationFactory::class);

		$builder->addDefinition(
			$this->prefix('protocol.factory.capability.motorControl'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Protocol\Capabilities\MotorControlFactory::class);

		$builder->addDefinition(
			$this->prefix('protocol.factory.capability.motorReverse'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Protocol\Capabilities\MotorReverseFactory::class);

		$builder->addDefinition(
			$this->prefix('protocol.factory.capability.percentage'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Protocol\Capabilities\PercentageFactory::class);

		$builder->addDefinition(
			$this->prefix('protocol.factory.capability.power'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Protocol\Capabilities\PowerFactory::class);

		$builder->addDefinition(
			$this->prefix('protocol.factory.capability.press'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Protocol\Capabilities\PressFactory::class);

		$builder->addDefinition(
			$this->prefix('protocol.factory.capability.rssi'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Protocol\Capabilities\RssiFactory::class);

		$builder->addDefinition(
			$this->prefix('protocol.factory.capability.startup'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Protocol\Capabilities\StartupFactory::class);

		$builder->addDefinition(
			$this->prefix('protocol.factory.capability.temperature'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Protocol\Capabilities\TemperatureFactory::class);

		$builder->addDefinition(
			$this->prefix('protocol.factory.capability.thermostat'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Protocol\Capabilities\ThermostatFactory::class);

		$builder->addDefinition(
			$this->prefix('protocol.factory.capability.thermostatModeDetect'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Protocol\Capabilities\ThermostatModeDetectFactory::class);

		$builder->addDefinition(
			$this->prefix('protocol.factory.capability.thermostatTargetSetPoint'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Protocol\Capabilities\ThermostatTargetSetPointFactory::class);

		$builder->addDefinition(
			$this->prefix('protocol.factory.capability.toggle'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Protocol\Capabilities\ToggleFactory::class);

		// ATTRIBUTES

		$builder->addDefinition(
			$this->prefix('protocol.factory.attribute.battery'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Protocol\Attributes\BatteryFactory::class);

		$builder->addDefinition(
			$this->prefix('protocol.factory.attribute.brightness'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Protocol\Attributes\BrightnessFactory::class);

		$builder->addDefinition(
			$this->prefix('protocol.factory.attribute.colorBlue'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Protocol\Attributes\ColorBlueFactory::class);

		$builder->addDefinition(
			$this->prefix('protocol.factory.attribute.colorGreen'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Protocol\Attributes\ColorGreenFactory::class);

		$builder->addDefinition(
			$this->prefix('protocol.factory.attribute.colorRed'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Protocol\Attributes\ColorRedFactory::class);

		$builder->addDefinition(
			$this->prefix('protocol.factory.attribute.colorTemperature'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Protocol\Attributes\ColorTemperatureFactory::class);

		$builder->addDefinition(
			$this->prefix('protocol.factory.attribute.detected'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Protocol\Attributes\DetectedFactory::class);

		$builder->addDefinition(
			$this->prefix('protocol.factory.attribute.fault'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Protocol\Attributes\FaultFactory::class);

		$builder->addDefinition(
			$this->prefix('protocol.factory.attribute.humidity'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Protocol\Attributes\HumidityFactory::class);

		$builder->addDefinition(
			$this->prefix('protocol.factory.attribute.illuminationLevel'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Protocol\Attributes\IlluminationLevelFactory::class);

		$builder->addDefinition(
			$this->prefix('protocol.factory.attribute.motorCalibration'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Protocol\Attributes\MotorCalibrationFactory::class);

		$builder->addDefinition(
			$this->prefix('protocol.factory.attribute.motorControl'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Protocol\Attributes\MotorControlFactory::class);

		$builder->addDefinition(
			$this->prefix('protocol.factory.attribute.motorReverse'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Protocol\Attributes\MotorReverseFactory::class);

		$builder->addDefinition(
			$this->prefix('protocol.factory.attribute.percentage'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Protocol\Attributes\PercentageFactory::class);

		$builder->addDefinition(
			$this->prefix('protocol.factory.attribute.powerState'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Protocol\Attributes\PowerStateFactory::class);

		$builder->addDefinition(
			$this->prefix('protocol.factory.attribute.press'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Protocol\Attributes\PressFactory::class);

		$builder->addDefinition(
			$this->prefix('protocol.factory.attribute.rssi'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Protocol\Attributes\RssiFactory::class);

		$builder->addDefinition(
			$this->prefix('protocol.factory.attribute.startup'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Protocol\Attributes\StartupFactory::class);

		$builder->addDefinition(
			$this->prefix('protocol.factory.attribute.temperature'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Protocol\Attributes\TemperatureFactory::class);

		$builder->addDefinition(
			$this->prefix('protocol.factory.attribute.thermostatAdaptiveRecoveryStatus'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Protocol\Attributes\ThermostatAdaptiveRecoveryStatusFactory::class);

		$builder->addDefinition(
			$this->prefix('protocol.factory.attribute.thermostatMode'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Protocol\Attributes\ThermostatModeFactory::class);

		$builder->addDefinition(
			$this->prefix('protocol.factory.attribute.thermostatModeDetection'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Protocol\Attributes\ThermostatModeDetectionFactory::class);

		$builder->addDefinition(
			$this->prefix('protocol.factory.attribute.thermostatTargetSetPoint'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Protocol\Attributes\ThermostatTargetSetPointFactory::class);

		$builder->addDefinition(
			$this->prefix('protocol.factory.attribute.toggleState'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Protocol\Attributes\ToggleStateFactory::class);

		// CONFIGURATIONS

		$builder->addDefinition(
			$this->prefix('protocol.factory.configuration.mappingMode'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Protocol\Configurations\MappingModeFactory::class);

		$builder->addDefinition(
			$this->prefix('protocol.factory.configuration.rangeMax'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Protocol\Configurations\RangeMaxFactory::class);

		$builder->addDefinition(
			$this->prefix('protocol.factory.configuration.rangeMin'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Protocol\Configurations\RangeMinFactory::class);

		$builder->addDefinition(
			$this->prefix('protocol.factory.configuration.streamUrl'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Protocol\Configurations\StreamUrlFactory::class);

		$builder->addDefinition(
			$this->prefix('protocol.factory.configuration.supportedDetectionLowerSetPointScale'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Protocol\Configurations\SupportedDetectionLowerSetPointScaleFactory::class);

		$builder->addDefinition(
			$this->prefix('protocol.factory.configuration.supportedDetectionLowerSetPointValue'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Protocol\Configurations\SupportedDetectionLowerSetPointValueFactory::class);

		$builder->addDefinition(
			$this->prefix('protocol.factory.configuration.supportedDetectionUpperSetPointScale'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Protocol\Configurations\SupportedDetectionUpperSetPointScaleFactory::class);

		$builder->addDefinition(
			$this->prefix('protocol.factory.configuration.supportedDetectionUpperSetPointValue'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Protocol\Configurations\SupportedDetectionUpperSetPointValueFactory::class);

		$builder->addDefinition(
			$this->prefix('protocol.factory.configuration.supportedDetectionModes'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Protocol\Configurations\SupportedDetectionModesFactory::class);

		$builder->addDefinition(
			$this->prefix('protocol.factory.configuration.temperatureIncrement'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Protocol\Configurations\TemperatureIncrementFactory::class);

		$builder->addDefinition(
			$this->prefix('protocol.factory.configuration.temperatureMax'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Protocol\Configurations\TemperatureMaxFactory::class);

		$builder->addDefinition(
			$this->prefix('protocol.factory.configuration.temperatureMin'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Protocol\Configurations\TemperatureMinFactory::class);

		$builder->addDefinition(
			$this->prefix('protocol.factory.configuration.temperatureScale'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Protocol\Configurations\TemperatureScaleFactory::class);

		$builder->addDefinition(
			$this->prefix('protocol.factory.configuration.thermostatSupportedModes'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Protocol\Configurations\ThermostatSupportedModesFactory::class);

		/**
		 * COMMANDS
		 */

		$builder->addDefinition($this->prefix('commands.execute'), new DI\Definitions\ServiceDefinition())
			->setType(Commands\Execute::class);

		$builder->addDefinition($this->prefix('commands.discover'), new DI\Definitions\ServiceDefinition())
			->setType(Commands\Discover::class);

		$builder->addDefinition($this->prefix('commands.install'), new DI\Definitions\ServiceDefinition())
			->setType(Commands\Install::class)
			->setArguments([
				'logger' => $logger,
			]);

		/**
		 * CONNECTOR
		 */

		$builder->addFactoryDefinition($this->prefix('connector'))
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
						'FastyBird\Connector\NsPanel\Entities',
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
						'FastyBird\Connector\NsPanel\Documents',
					]);
				}
			}
		}

		/**
		 * PANEL PROTOCOL
		 */

		$protocolLoaderServiceName = $builder->getByType(Protocol\Loader::class);

		if ($protocolLoaderServiceName !== null) {
			$protocolLoaderService = $builder->getDefinition($protocolLoaderServiceName);
			assert($protocolLoaderService instanceof DI\Definitions\ServiceDefinition);

			$devicesFactories = $builder->findByType(
				Protocol\Devices\DeviceFactory::class,
			);
			$capabilitiesFactories = $builder->findByType(
				Protocol\Capabilities\CapabilityFactory::class,
			);
			$attributesFactories = $builder->findByType(
				Protocol\Attributes\AttributeFactory::class,
			);
			$configurationsFactories = $builder->findByType(
				Protocol\Configurations\ConfigurationFactory::class,
			);

			$protocolLoaderService->setArgument('devicesFactories', $devicesFactories);
			$protocolLoaderService->setArgument('capabilitiesFactories', $capabilitiesFactories);
			$protocolLoaderService->setArgument('attributesFactories', $attributesFactories);
			$protocolLoaderService->setArgument('configurationsFactories', $configurationsFactories);
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
