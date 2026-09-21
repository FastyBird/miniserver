<?php declare(strict_types = 1);

namespace FastyBird\Connector\Zigbee2Mqtt\Tests\Cases\Unit\Queue\Consumers;

use Doctrine\DBAL;
use Error;
use Exception;
use FastyBird\Connector\Zigbee2Mqtt\Entities;
use FastyBird\Connector\Zigbee2Mqtt\Exceptions;
use FastyBird\Connector\Zigbee2Mqtt\Helpers;
use FastyBird\Connector\Zigbee2Mqtt\Queries;
use FastyBird\Connector\Zigbee2Mqtt\Queue;
use FastyBird\Connector\Zigbee2Mqtt\Tests;
use FastyBird\Core\Documents\Application as ApplicationDocuments;
use FastyBird\Core\Exceptions as ApplicationExceptions;
use FastyBird\Core\Messaging\Exchange\Publisher as ExchangePublisher;
use FastyBird\Core\Types\Metadata as MetadataTypes;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use Nette;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Ramsey\Uuid;
use RuntimeException;
use function assert;

#[PreserveGlobalState(false)]
#[RunTestsInSeparateProcesses]
final class StoreBridgeDevicesTest extends Tests\Cases\Unit\DbTestCase
{

	/**
	 * @throws DBAL\Exception
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws DevicesExceptions\InvalidState
	 * @throws DevicesExceptions\Runtime
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 * @throws Exception
	 */
	public function testConsumeMessage(): void
	{
		$publisher = $this->createMock(ExchangePublisher\Container::class);
		$publisher
			->expects(self::exactly(33))
			->method('publish')
			->with(
				self::callback(
					static function (MetadataTypes\Sources\Source $source): bool {
						self::assertTrue($source === MetadataTypes\Sources\Module::DEVICES);

						return true;
					},
				),
				self::callback(static fn (string $routingKey): bool => true),
				self::callback(static function (ApplicationDocuments\Document|null $document): bool {
					self::assertTrue($document !== null);

					return true;
				}),
			);

		$this->mockContainerService(
			ExchangePublisher\Container::class,
			$publisher,
		);

		$consumer = $this->getContainer()->getByType(
			Queue\Consumers\StoreBridgeDevices::class,
		);

		$messageBuilder = $this->getContainer()->getByType(
			Helpers\MessageBuilder::class,
		);

		$message = $messageBuilder->create(
			Queue\Messages\StoreBridgeDevices::class,
			[
				'connector' => Uuid\Uuid::fromString('f15d2072-fb60-421a-a85f-2566e4dc13fe'),
				'base_topic' => 'zigbee2mqtt',
				'devices' => [
					[
						'friendly_name' => '0xa4c138f06eafa3da',
						'ieee_address' => '0xa4c138f06eafa3da',
						'network_address' => 37_167,
						'interview_completed' => true,
						'interviewing' => false,
						'type' => 'EndDevice',
						'definition' => [
							'model' => 'SEN123',
							'vendor' => 'VendorName',
							'description' => 'Some sensor',
							'exposes' => [
								[
									'access' => 1,
									'description' => 'Indicates if the battery of this device is almost empty',
									'label' => 'Battery low',
									'name' => 'battery_low',
									'property' => 'battery_low',
									'type' => 'binary',
									'value_off' => false,
									'value_on' => true,
								],
								[
									'access' => 1,
									'description' => 'Remaining battery in %, can take up to 24 hours before reported.',
									'label' => 'Battery',
									'name' => 'battery',
									'property' => 'battery',
									'type' => 'numeric',
									'unit' => '%',
									'value_max' => 100,
									'value_min' => 0,
								],
								[
									'label' => 'Day time',
									'name' => 'day_time',
									'property' => 'day_time',
									'type' => 'composite',
									'features' => [
										[
											'access' => 3,
											'label' => 'Day',
											'name' => 'day',
											'property' => 'day',
											'type' => 'enum',
											'values' => ['monday', 'tuesday', 'wednesday'],
										],
										[
											'access' => 3,
											'label' => 'Hour',
											'name' => 'hour',
											'property' => 'hour',
											'type' => 'numeric',
										],
										[
											'access' => 3,
											'label' => 'Minute',
											'name' => 'minute',
											'property' => 'minute',
											'type' => 'numeric',
										],
									],
								],
							],
							'supports_ota' => false,
						],
						'description' => null,
						'manufacturer' => null,
						'model_id' => 'SEN123',
						'supported' => true,
						'disabled' => false,
					],
					[
						'friendly_name' => '0xa5c129a06eafa3da',
						'ieee_address' => '0xa5c129a06eafa3da',
						'network_address' => 30_167,
						'interview_completed' => true,
						'interviewing' => false,
						'type' => 'EndDevice',
						'definition' => [
							'model' => 'SEN321',
							'vendor' => 'VendorName',
							'description' => 'Other sensor',
							'exposes' => [
								[
									'type' => 'light',
									'features' => [
										[
											'access' => 7,
											'label' => 'State',
											'name' => 'state',
											'property' => 'state',
											'type' => 'binary',
											'value_off' => 'OFF',
											'value_on' => 'ON',
											'value_toggle' => 'TOGGLE',
										],
										[
											'access' => 7,
											'label' => 'Brightness',
											'name' => 'brightness',
											'property' => 'brightness',
											'type' => 'numeric',
											'value_min' => 0,
											'value_max' => 254,
										],
										[
											'label' => 'Color xy',
											'name' => 'color_xy',
											'property' => 'color',
											'type' => 'composite',
											'features' => [
												[
													'access' => 7,
													'label' => 'X',
													'name' => 'x',
													'property' => 'x',
													'type' => 'numeric',
												],
												[
													'access' => 7,
													'label' => 'Y',
													'name' => 'y',
													'property' => 'y',
													'type' => 'numeric',
												],
											],
										],
									],
								],
							],
							'supports_ota' => false,
						],
						'description' => null,
						'manufacturer' => null,
						'model_id' => 'SEN321',
						'supported' => true,
						'disabled' => false,
					],
				],
			],
		);

		$consumer->consume($message);

		$connectorsRepository = $this->getContainer()->getByType(
			DevicesModels\Entities\Connectors\ConnectorsRepository::class,
		);

		$connector = $connectorsRepository->find(
			Uuid\Uuid::fromString('f15d2072-fb60-421a-a85f-2566e4dc13fe'),
			Entities\Connectors\Connector::class,
		);
		assert($connector instanceof Entities\Connectors\Connector);

		$devicesRepository = $this->getContainer()->getByType(
			DevicesModels\Entities\Devices\DevicesRepository::class,
		);

		$findDevicesQuery = new Queries\Entities\FindBridgeDevices();
		$findDevicesQuery->forConnector($connector);

		$bridge = $devicesRepository->findOneBy($findDevicesQuery, Entities\Devices\Bridge::class);
		assert($bridge instanceof Entities\Devices\Bridge);

		$findDevicesQuery = new Queries\Entities\FindSubDevices();
		$findDevicesQuery->forConnector($connector);
		$findDevicesQuery->forParent($bridge);

		$devices = $devicesRepository->findAllBy($findDevicesQuery, Entities\Devices\SubDevice::class);

		self::assertCount(2, $devices);
	}

}
