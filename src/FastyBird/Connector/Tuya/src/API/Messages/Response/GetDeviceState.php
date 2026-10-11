<?php declare(strict_types = 1);

namespace FastyBird\Connector\Tuya\API\Messages\Response;

use FastyBird\Connector\Tuya\API;
use Orisai\ObjectMapper;
use Override;
use function array_map;

/**
 * OpenAPI get device state message
 */
readonly class GetDeviceState implements API\Messages\Message
{

	/**
	 * @param array<DeviceDataPointState> $result
	 */
	public function __construct(
		#[ObjectMapper\Rules\ArrayOf(
			new ObjectMapper\Rules\MappedObjectValue(DeviceDataPointState::class),
		)]
		private array $result,
	)
	{
	}

	/**
	 * @return array<DeviceDataPointState>
	 */
	public function getResult(): array
	{
		return $this->result;
	}

	#[Override]
	public function toArray(): array
	{
		return [
			'result' => array_map(
				static fn (DeviceDataPointState $status): array => $status->toArray(),
				$this->getResult(),
			),
		];
	}

}
