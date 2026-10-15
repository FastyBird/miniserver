<?php declare(strict_types = 1);

namespace FastyBird\Connector\NsPanel\Tests\Cases\Unit\Queue\Consumers;

use Error;
use FastyBird\Connector\NsPanel\Entities as NsPanelEntities;
use FastyBird\Connector\NsPanel\Exceptions as NsPanelExceptions;
use FastyBird\Connector\NsPanel\Helpers;
use FastyBird\Connector\NsPanel\Queries;
use FastyBird\Connector\NsPanel\Queue;
use FastyBird\Connector\NsPanel\Tests;
use FastyBird\Core\Exceptions as CoreExceptions;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use FastyBird\Module\Devices\Models as DevicesModels;
use Nette;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Ramsey\Uuid;
use RuntimeException;
use Throwable;
use function array_map;
use function ksort;

/**
 * What storing a sub-device the gateway reports stores, field by field, the first time and again
 */
#[PreserveGlobalState(false)]
#[RunTestsInSeparateProcesses]
final class StoreSubDeviceTest extends Tests\Cases\Unit\DbTestCase
{

	private const string CONNECTOR_ID = '3c75e7f0-bdfe-407a-8230-83dbbbdf0155';

	private const string GATEWAY_ID = '896a5f35-7c9a-47f2-9c72-f1520d503364';

	private const string SERIAL_NUMBER = 'pin-sub-device';

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws NsPanelExceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 * @throws Throwable
	 */
	public function testStoreSubDevice(): void
	{
		$this->storeSubDevice();

		$stored = $this->stored($this->subDevice());

		self::assertSame(
			[
				'type' => 'ns-panel-connector-sub-device',
				'identifier' => 'pin-sub-device',
				'name' => 'Pinned lamp',
				'connector' => 'ns-panel',
				'parents' => [
					'Dummy NS Panel',
				],
				'properties' => [
					'category' => [
						'type' => 'variable',
						'category' => 'generic',
						'identifier' => 'category',
						'name' => 'Category',
						'data_type' => 'string',
						'unit' => null,
						'format' => null,
						'invalid' => null,
						'scale' => null,
						'step' => null,
						'default' => null,
						'value_transformer' => null,
						'value' => 'light',
						'children' => [],
					],
					'firmware_version' => [
						'type' => 'variable',
						'category' => 'generic',
						'identifier' => 'firmware_version',
						'name' => 'Firmware version',
						'data_type' => 'string',
						'unit' => null,
						'format' => null,
						'invalid' => null,
						'scale' => null,
						'step' => null,
						'default' => null,
						'value_transformer' => null,
						'value' => '1.2.3',
						'children' => [],
					],
					'hardware_mac_address' => [
						'type' => 'variable',
						'category' => 'generic',
						'identifier' => 'hardware_mac_address',
						'name' => 'Hardware MAC address',
						'data_type' => 'string',
						'unit' => null,
						'format' => null,
						'invalid' => null,
						'scale' => null,
						'step' => null,
						'default' => null,
						'value_transformer' => null,
						'value' => '00:11:22:33:44:55',
						'children' => [],
					],
					'hardware_manufacturer' => [
						'type' => 'variable',
						'category' => 'generic',
						'identifier' => 'hardware_manufacturer',
						'name' => 'Hardware manufacturer',
						'data_type' => 'string',
						'unit' => null,
						'format' => null,
						'invalid' => null,
						'scale' => null,
						'step' => null,
						'default' => null,
						'value_transformer' => null,
						'value' => 'Maker',
						'children' => [],
					],
					'hardware_model' => [
						'type' => 'variable',
						'category' => 'generic',
						'identifier' => 'hardware_model',
						'name' => 'Hardware model',
						'data_type' => 'string',
						'unit' => null,
						'format' => null,
						'invalid' => null,
						'scale' => null,
						'step' => null,
						'default' => null,
						'value_transformer' => null,
						'value' => 'L1',
						'children' => [],
					],
					'state' => [
						'type' => 'dynamic',
						'category' => 'generic',
						'identifier' => 'state',
						'name' => 'State',
						'data_type' => 'enum',
						'unit' => null,
						'format' => [
							'connected',
							'disconnected',
							'alert',
							'unknown',
						],
						'invalid' => null,
						'scale' => null,
						'step' => null,
						'default' => null,
						'value_transformer' => null,
						'settable' => false,
						'queryable' => false,
						'children' => [],
					],
				],
				'channels' => [
					'brightness' => [
						'type' => 'ns-panel-connector-brightness',
						'name' => null,
						'properties' => [
							'brightness' => [
								'type' => 'dynamic',
								'category' => 'generic',
								'identifier' => 'brightness',
								'name' => null,
								'data_type' => 'uchar',
								'unit' => null,
								'format' => [
									0.0,
									100.0,
								],
								'invalid' => null,
								'scale' => null,
								'step' => null,
								'default' => null,
								'value_transformer' => null,
								'settable' => true,
								'queryable' => true,
								'children' => [],
							],
						],
					],
					'power' => [
						'type' => 'ns-panel-connector-power',
						'name' => null,
						'properties' => [
							'powerState' => [
								'type' => 'dynamic',
								'category' => 'generic',
								'identifier' => 'powerState',
								'name' => null,
								'data_type' => 'switch',
								'unit' => null,
								'format' => [
									[
										'switch_on',
										'on',
										'on',
									],
									[
										'switch_off',
										'off',
										'off',
									],
								],
								'invalid' => null,
								'scale' => null,
								'step' => null,
								'default' => null,
								'value_transformer' => null,
								'settable' => true,
								'queryable' => true,
								'children' => [],
							],
						],
					],
				],
			],
			$stored,
		);

		// the second time, every property and channel is there already and is updated
		$this->storeSubDevice();

		self::assertSame($stored, $this->stored($this->subDevice()));
	}

	/**
	 * @throws Throwable
	 */
	private function storeSubDevice(): void
	{
		$consumed = $this->getContainer()->getByType(Queue\Consumers\StoreSubDevice::class)->consume(
			$this->getContainer()->getByType(Helpers\MessageBuilder::class)->create(
				Queue\Messages\StoreSubDevice::class,
				[
					'connector' => Uuid\Uuid::fromString(self::CONNECTOR_ID),
					'gateway' => Uuid\Uuid::fromString(self::GATEWAY_ID),
					'serial_number' => self::SERIAL_NUMBER,
					'name' => 'Pinned lamp',
					'manufacturer' => 'Maker',
					'model' => 'L1',
					'firmware_version' => '1.2.3',
					'display_category' => 'light',
					'mac_address' => '00:11:22:33:44:55',
					'capabilities' => [
						['capability' => 'power', 'permission' => 'readWrite'],
						['capability' => 'brightness', 'permission' => 'readWrite'],
					],
					'state' => [
						['capability' => 'power', 'attribute' => 'powerState', 'value' => 'on'],
						['capability' => 'brightness', 'attribute' => 'brightness', 'value' => 50],
					],
				],
			),
		);

		self::assertTrue($consumed);

		$this->getEntityManager()->clear();
	}

	/**
	 * @return array<string, mixed>
	 *
	 * @throws Throwable
	 */
	private function stored(NsPanelEntities\Devices\SubDevice $device): array
	{
		$channels = [];

		foreach ($device->getChannels() as $channel) {
			$channels[$channel->getIdentifier()] = [
				'type' => $channel::getType(),
				'name' => $channel->getName(),
				'properties' => $this->properties($channel->getProperties()),
			];
		}

		ksort($channels);

		return [
			'type' => $device::getType(),
			'identifier' => $device->getIdentifier(),
			'name' => $device->getName(),
			'connector' => $device->getConnector()->getIdentifier(),
			'parents' => array_map(
				static fn (DevicesEntities\Devices\Device $parent): string => $parent->getIdentifier(),
				$device->getParents(),
			),
			'properties' => $this->properties($device->getProperties()),
			'channels' => $channels,
		];
	}

	/**
	 * @param array<DevicesEntities\Property> $properties
	 *
	 * @return array<string, array<string, mixed>>
	 *
	 * @throws Throwable
	 */
	private function properties(array $properties): array
	{
		$data = [];

		foreach ($properties as $property) {
			$item = $property->toArray();

			unset($item['id'], $item['created_at'], $item['updated_at'], $item['device'], $item['channel'], $item['owner']);

			$data[$property->getIdentifier()] = $item;
		}

		ksort($data);

		return $data;
	}

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws NsPanelExceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 */
	private function subDevice(): NsPanelEntities\Devices\SubDevice
	{
		$findDeviceQuery = new Queries\Entities\FindSubDevices();
		$findDeviceQuery->byIdentifier(self::SERIAL_NUMBER);

		$device = $this->getContainer()->getByType(DevicesModels\Entities\Devices\DevicesRepository::class)->findOneBy(
			$findDeviceQuery,
			NsPanelEntities\Devices\SubDevice::class,
		);

		self::assertInstanceOf(NsPanelEntities\Devices\SubDevice::class, $device);

		return $device;
	}

}
