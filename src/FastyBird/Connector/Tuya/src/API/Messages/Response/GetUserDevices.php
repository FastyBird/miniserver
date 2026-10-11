<?php declare(strict_types = 1);

namespace FastyBird\Connector\Tuya\API\Messages\Response;

use FastyBird\Connector\Tuya\API;
use Orisai\ObjectMapper;
use function array_map;

/**
 * OpenAPI get user devices message
 */
readonly class GetUserDevices implements API\Messages\Message
{

	/**
	 * @param array<UserDevice> $result
	 */
	public function __construct(
		#[ObjectMapper\Rules\ArrayOf(
			new ObjectMapper\Rules\MappedObjectValue(UserDevice::class),
		)]
		private array $result,
	)
	{
	}

	/**
	 * @return array<UserDevice>
	 */
	public function getResult(): array
	{
		return $this->result;
	}

	public function toArray(): array
	{
		return [
			'result' => array_map(
				static fn (UserDevice $status): array => $status->toArray(),
				$this->getResult(),
			),
		];
	}

}
