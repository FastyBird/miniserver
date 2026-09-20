<?php declare(strict_types = 1);

namespace FastyBird\Connector\NsPanel\Tests\Cases\Unit\DI;

use Error;
use FastyBird\Connector\NsPanel\API;
use FastyBird\Connector\NsPanel\Clients;
use FastyBird\Connector\NsPanel\Commands;
use FastyBird\Connector\NsPanel\Connector;
use FastyBird\Connector\NsPanel\Controllers;
use FastyBird\Connector\NsPanel\Helpers;
use FastyBird\Connector\NsPanel\Hydrators;
use FastyBird\Connector\NsPanel\Mapping;
use FastyBird\Connector\NsPanel\Middleware;
use FastyBird\Connector\NsPanel\Protocol;
use FastyBird\Connector\NsPanel\Queue;
use FastyBird\Connector\NsPanel\Schemas;
use FastyBird\Connector\NsPanel\Servers;
use FastyBird\Connector\NsPanel\Services;
use FastyBird\Connector\NsPanel\Subscribers;
use FastyBird\Connector\NsPanel\Tests;
use FastyBird\Connector\NsPanel\Writers;
use FastyBird\Core\Exceptions as ApplicationExceptions;
use Nette;

final class NsPanelExtensionTest extends Tests\Cases\Unit\BaseTestCase
{

	/**
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws Nette\DI\MissingServiceException
	 * @throws Error
	 */
	public function testServicesRegistration(): void
	{
		$container = $this->createContainer();

		self::assertCount(2, $container->findByType(Writers\WriterFactory::class));

		self::assertNotNull($container->getByType(Clients\GatewayFactory::class, false));
		self::assertNotNull($container->getByType(Clients\DeviceFactory::class, false));
		self::assertNotNull($container->getByType(Clients\DiscoveryFactory::class, false));

		self::assertNotNull($container->getByType(Services\HttpClientFactory::class, false));

		self::assertNotNull($container->getByType(API\LanApiFactory::class, false));

		self::assertNotNull($container->getByType(Servers\HttpFactory::class, false));

		self::assertNotNull($container->getByType(Queue\Consumers\StoreDeviceState::class, false));
		self::assertNotNull($container->getByType(Queue\Consumers\StoreDeviceConnectionState::class, false));
		self::assertNotNull($container->getByType(Queue\Consumers\StoreThirdPartyDevice::class, false));
		self::assertNotNull($container->getByType(Queue\Consumers\StoreSubDevice::class, false));
		self::assertNotNull($container->getByType(Queue\Consumers\WriteSubDeviceState::class, false));
		self::assertNotNull($container->getByType(Queue\Consumers\WriteThirdPartyDeviceState::class, false));
		self::assertNotNull($container->getByType(Queue\Consumers::class, false));
		self::assertNotNull($container->getByType(Queue\Queue::class, false));

		self::assertNotNull($container->getByType(Subscribers\Devices::class, false));
		self::assertNotNull($container->getByType(Subscribers\Properties::class, false));
		self::assertNotNull($container->getByType(Subscribers\Controls::class, false));

		self::assertNotNull($container->getByType(Schemas\Connectors\Connector::class, false));
		self::assertNotNull($container->getByType(Schemas\Devices\Gateway::class, false));
		self::assertNotNull($container->getByType(Schemas\Devices\SubDevice::class, false));
		self::assertNotNull($container->getByType(Schemas\Devices\ThirdPartyDevice::class, false));
		self::assertNotNull($container->getByType(Schemas\Channels\Battery::class, false));
		self::assertNotNull($container->getByType(Schemas\Channels\Brightness::class, false));
		self::assertNotNull($container->getByType(Schemas\Channels\CameraStream::class, false));
		self::assertNotNull($container->getByType(Schemas\Channels\ColorRgb::class, false));
		self::assertNotNull($container->getByType(Schemas\Channels\ColorTemperature::class, false));
		self::assertNotNull($container->getByType(Schemas\Channels\Detect::class, false));
		self::assertNotNull($container->getByType(Schemas\Channels\Fault::class, false));
		self::assertNotNull($container->getByType(Schemas\Channels\Humidity::class, false));
		self::assertNotNull($container->getByType(Schemas\Channels\IlluminationLevel::class, false));
		self::assertNotNull($container->getByType(Schemas\Channels\MotorCalibration::class, false));
		self::assertNotNull($container->getByType(Schemas\Channels\MotorControl::class, false));
		self::assertNotNull($container->getByType(Schemas\Channels\MotorReverse::class, false));
		self::assertNotNull($container->getByType(Schemas\Channels\Percentage::class, false));
		self::assertNotNull($container->getByType(Schemas\Channels\Power::class, false));
		self::assertNotNull($container->getByType(Schemas\Channels\Rssi::class, false));
		self::assertNotNull($container->getByType(Schemas\Channels\Startup::class, false));
		self::assertNotNull($container->getByType(Schemas\Channels\Temperature::class, false));
		self::assertNotNull($container->getByType(Schemas\Channels\Thermostat::class, false));
		self::assertNotNull($container->getByType(Schemas\Channels\ThermostatModeDetect::class, false));
		self::assertNotNull($container->getByType(Schemas\Channels\ThermostatTargetSetPoint::class, false));

		self::assertNotNull($container->getByType(Hydrators\Connectors\Connector::class, false));
		self::assertNotNull($container->getByType(Hydrators\Devices\Gateway::class, false));
		self::assertNotNull($container->getByType(Hydrators\Devices\SubDevice::class, false));
		self::assertNotNull($container->getByType(Hydrators\Devices\ThirdPartyDevice::class, false));
		self::assertNotNull($container->getByType(Hydrators\Channels\Battery::class, false));
		self::assertNotNull($container->getByType(Hydrators\Channels\Brightness::class, false));
		self::assertNotNull($container->getByType(Hydrators\Channels\CameraStream::class, false));
		self::assertNotNull($container->getByType(Hydrators\Channels\ColorRgb::class, false));
		self::assertNotNull($container->getByType(Hydrators\Channels\ColorTemperature::class, false));
		self::assertNotNull($container->getByType(Hydrators\Channels\Detect::class, false));
		self::assertNotNull($container->getByType(Hydrators\Channels\Fault::class, false));
		self::assertNotNull($container->getByType(Hydrators\Channels\Humidity::class, false));
		self::assertNotNull($container->getByType(Hydrators\Channels\IlluminationLevel::class, false));
		self::assertNotNull($container->getByType(Hydrators\Channels\MotorCalibration::class, false));
		self::assertNotNull($container->getByType(Hydrators\Channels\MotorControl::class, false));
		self::assertNotNull($container->getByType(Hydrators\Channels\MotorReverse::class, false));
		self::assertNotNull($container->getByType(Hydrators\Channels\Percentage::class, false));
		self::assertNotNull($container->getByType(Hydrators\Channels\Power::class, false));
		self::assertNotNull($container->getByType(Hydrators\Channels\Rssi::class, false));
		self::assertNotNull($container->getByType(Hydrators\Channels\Startup::class, false));
		self::assertNotNull($container->getByType(Hydrators\Channels\Temperature::class, false));
		self::assertNotNull($container->getByType(Hydrators\Channels\Thermostat::class, false));
		self::assertNotNull($container->getByType(Hydrators\Channels\ThermostatModeDetect::class, false));
		self::assertNotNull($container->getByType(Hydrators\Channels\ThermostatTargetSetPoint::class, false));

		self::assertNotNull($container->getByType(Helpers\MessageBuilder::class, false));
		self::assertNotNull($container->getByType(Helpers\Connectors\Connector::class, false));
		self::assertNotNull($container->getByType(Helpers\Devices\Gateway::class, false));
		self::assertNotNull($container->getByType(Helpers\Devices\ThirdPartyDevice::class, false));
		self::assertNotNull($container->getByType(Helpers\Devices\SubDevice::class, false));
		self::assertNotNull($container->getByType(Helpers\Channels\Channel::class, false));

		self::assertNotNull($container->getByType(Middleware\Router::class, false));

		self::assertNotNull($container->getByType(Mapping\Builder::class, false));

		self::assertNotNull($container->getByType(Protocol\Loader::class, false));
		self::assertNotNull($container->getByType(Protocol\Driver::class, false));
		self::assertNotNull($container->getByType(Protocol\Devices\SubDeviceFactory::class, false));
		self::assertNotNull($container->getByType(Protocol\Devices\ThirdPartyDeviceFactory::class, false));
		self::assertNotNull($container->getByType(Protocol\Capabilities\BatteryFactory::class, false));
		self::assertNotNull($container->getByType(Protocol\Capabilities\BrightnessFactory::class, false));
		self::assertNotNull($container->getByType(Protocol\Capabilities\CameraStreamFactory::class, false));
		self::assertNotNull($container->getByType(Protocol\Capabilities\ColorRgbFactory::class, false));
		self::assertNotNull($container->getByType(Protocol\Capabilities\ColorTemperatureFactory::class, false));
		self::assertNotNull($container->getByType(Protocol\Capabilities\DetectFactory::class, false));
		self::assertNotNull($container->getByType(Protocol\Capabilities\FaultFactory::class, false));
		self::assertNotNull($container->getByType(Protocol\Capabilities\HumidityFactory::class, false));
		self::assertNotNull($container->getByType(Protocol\Capabilities\IlluminationLevelFactory::class, false));
		self::assertNotNull($container->getByType(Protocol\Capabilities\MotorCalibrationFactory::class, false));
		self::assertNotNull($container->getByType(Protocol\Capabilities\MotorControlFactory::class, false));
		self::assertNotNull($container->getByType(Protocol\Capabilities\MotorReverseFactory::class, false));
		self::assertNotNull($container->getByType(Protocol\Capabilities\PercentageFactory::class, false));
		self::assertNotNull($container->getByType(Protocol\Capabilities\PowerFactory::class, false));
		self::assertNotNull($container->getByType(Protocol\Capabilities\PressFactory::class, false));
		self::assertNotNull($container->getByType(Protocol\Capabilities\RssiFactory::class, false));
		self::assertNotNull($container->getByType(Protocol\Capabilities\StartupFactory::class, false));
		self::assertNotNull($container->getByType(Protocol\Capabilities\TemperatureFactory::class, false));
		self::assertNotNull($container->getByType(Protocol\Capabilities\ThermostatFactory::class, false));
		self::assertNotNull($container->getByType(Protocol\Capabilities\ThermostatModeDetectFactory::class, false));
		self::assertNotNull($container->getByType(Protocol\Capabilities\ThermostatTargetSetPointFactory::class, false));
		self::assertNotNull($container->getByType(Protocol\Capabilities\ToggleFactory::class, false));
		self::assertNotNull($container->getByType(Protocol\Attributes\BatteryFactory::class, false));
		self::assertNotNull($container->getByType(Protocol\Attributes\BrightnessFactory::class, false));
		self::assertNotNull($container->getByType(Protocol\Attributes\ColorBlueFactory::class, false));
		self::assertNotNull($container->getByType(Protocol\Attributes\ColorGreenFactory::class, false));
		self::assertNotNull($container->getByType(Protocol\Attributes\ColorRedFactory::class, false));
		self::assertNotNull($container->getByType(Protocol\Attributes\ColorTemperatureFactory::class, false));
		self::assertNotNull($container->getByType(Protocol\Attributes\DetectedFactory::class, false));
		self::assertNotNull($container->getByType(Protocol\Attributes\FaultFactory::class, false));
		self::assertNotNull($container->getByType(Protocol\Attributes\HumidityFactory::class, false));
		self::assertNotNull($container->getByType(Protocol\Attributes\IlluminationLevelFactory::class, false));
		self::assertNotNull($container->getByType(Protocol\Attributes\MotorCalibrationFactory::class, false));
		self::assertNotNull($container->getByType(Protocol\Attributes\MotorControlFactory::class, false));
		self::assertNotNull($container->getByType(Protocol\Attributes\MotorReverseFactory::class, false));
		self::assertNotNull($container->getByType(Protocol\Attributes\PercentageFactory::class, false));
		self::assertNotNull($container->getByType(Protocol\Attributes\PowerStateFactory::class, false));
		self::assertNotNull($container->getByType(Protocol\Attributes\PressFactory::class, false));
		self::assertNotNull($container->getByType(Protocol\Attributes\RssiFactory::class, false));
		self::assertNotNull($container->getByType(Protocol\Attributes\StartupFactory::class, false));
		self::assertNotNull($container->getByType(Protocol\Attributes\TemperatureFactory::class, false));
		self::assertNotNull(
			$container->getByType(Protocol\Attributes\ThermostatAdaptiveRecoveryStatusFactory::class, false),
		);
		self::assertNotNull($container->getByType(Protocol\Attributes\ThermostatModeFactory::class, false));
		self::assertNotNull($container->getByType(Protocol\Attributes\ThermostatModeDetectionFactory::class, false));
		self::assertNotNull($container->getByType(Protocol\Attributes\ThermostatTargetSetPointFactory::class, false));
		self::assertNotNull($container->getByType(Protocol\Attributes\ToggleStateFactory::class, false));
		self::assertNotNull($container->getByType(Protocol\Configurations\MappingModeFactory::class, false));
		self::assertNotNull($container->getByType(Protocol\Configurations\RangeMaxFactory::class, false));
		self::assertNotNull($container->getByType(Protocol\Configurations\RangeMinFactory::class, false));
		self::assertNotNull($container->getByType(Protocol\Configurations\StreamUrlFactory::class, false));
		self::assertNotNull(
			$container->getByType(Protocol\Configurations\SupportedDetectionLowerSetPointScaleFactory::class, false),
		);
		self::assertNotNull(
			$container->getByType(Protocol\Configurations\SupportedDetectionLowerSetPointValueFactory::class, false),
		);
		self::assertNotNull(
			$container->getByType(Protocol\Configurations\SupportedDetectionUpperSetPointScaleFactory::class, false),
		);
		self::assertNotNull(
			$container->getByType(Protocol\Configurations\SupportedDetectionUpperSetPointValueFactory::class, false),
		);
		self::assertNotNull(
			$container->getByType(Protocol\Configurations\SupportedDetectionModesFactory::class, false),
		);
		self::assertNotNull($container->getByType(Protocol\Configurations\TemperatureIncrementFactory::class, false));
		self::assertNotNull($container->getByType(Protocol\Configurations\TemperatureMaxFactory::class, false));
		self::assertNotNull($container->getByType(Protocol\Configurations\TemperatureMinFactory::class, false));
		self::assertNotNull($container->getByType(Protocol\Configurations\TemperatureScaleFactory::class, false));
		self::assertNotNull(
			$container->getByType(Protocol\Configurations\ThermostatSupportedModesFactory::class, false),
		);

		self::assertNotNull($container->getByType(Controllers\DirectiveController::class, false));

		self::assertNotNull($container->getByType(Commands\Execute::class, false));
		self::assertNotNull($container->getByType(Commands\Discover::class, false));
		self::assertNotNull($container->getByType(Commands\Install::class, false));

		self::assertNotNull($container->getByType(Connector\ConnectorFactory::class, false));
	}

}
