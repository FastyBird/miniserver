<?php declare(strict_types = 1);

namespace FastyBird\Connector\Tuya\API\Messages\Response;

use FastyBird\Connector\Tuya\API;
use Orisai\ObjectMapper;
use Override;

/**
 * OpenAPI get devices message
 */
readonly class GetDevices implements API\Messages\Message
{

	public function __construct(
		#[ObjectMapper\Rules\MappedObjectValue(GetDevicesResult::class)]
		private GetDevicesResult $result,
	)
	{
	}

	public function getResult(): GetDevicesResult
	{
		return $this->result;
	}

	#[Override]
	public function toArray(): array
	{
		return [
			'result' => $this->getResult()->toArray(),
		];
	}

}
