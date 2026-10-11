<?php declare(strict_types = 1);

namespace FastyBird\Connector\Tuya\API\Messages\Response;

use FastyBird\Connector\Tuya\API;
use Orisai\ObjectMapper;
use function array_map;

/**
 * OpenAPI get user device state message
 */
readonly class GetUserDeviceState implements API\Messages\Message
{

	/**
	 * @param array<UserDeviceDataPointState> $result
	 */
	public function __construct(
		#[ObjectMapper\Rules\ArrayOf(
			new ObjectMapper\Rules\MappedObjectValue(UserDeviceDataPointState::class),
		)]
		private array $result,
	)
	{
	}

	/**
	 * @return array<UserDeviceDataPointState>
	 */
	public function getResult(): array
	{
		return $this->result;
	}

	public function toArray(): array
	{
		return [
			'result' => array_map(
				static fn (UserDeviceDataPointState $status): array => $status->toArray(),
				$this->getResult(),
			),
		];
	}

}
