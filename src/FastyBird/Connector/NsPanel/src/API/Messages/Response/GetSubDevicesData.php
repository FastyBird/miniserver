<?php declare(strict_types = 1);

namespace FastyBird\Connector\NsPanel\API\Messages\Response;

use FastyBird\Connector\NsPanel\API;
use Orisai\ObjectMapper;
use stdClass;
use function array_map;

/**
 * Get NS Panel sub-devices list data response definition
 */
final readonly class GetSubDevicesData implements API\Messages\Message
{

	/**
	 * @param array<GetSubDevicesDataSubDevice> $devicesList
	 */
	public function __construct(
		#[ObjectMapper\Rules\ArrayOf(
			new ObjectMapper\Rules\MappedObjectValue(GetSubDevicesDataSubDevice::class),
		)]
		#[ObjectMapper\Modifiers\FieldName('device_list')]
		private array $devicesList,
	)
	{
	}

	/**
	 * @return array<GetSubDevicesDataSubDevice>
	 */
	public function getDevicesList(): array
	{
		return $this->devicesList;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'devices_list' => array_map(
				static fn (GetSubDevicesDataSubDevice $device): array => $device->toArray(),
				$this->getDevicesList(),
			),
		];
	}

	public function toJson(): object
	{
		$json = new stdClass();
		$json->device_list = array_map(
			static fn (GetSubDevicesDataSubDevice $device): object => $device->toJson(),
			$this->getDevicesList(),
		);

		return $json;
	}

}
