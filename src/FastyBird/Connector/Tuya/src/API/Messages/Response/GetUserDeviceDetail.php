<?php declare(strict_types = 1);

namespace FastyBird\Connector\Tuya\API\Messages\Response;

use FastyBird\Connector\Tuya\API;
use Orisai\ObjectMapper;
use Override;

/**
 * OpenAPI get user device message
 */
final readonly class GetUserDeviceDetail implements API\Messages\Message
{

	public function __construct(
		#[ObjectMapper\Rules\MappedObjectValue(UserDevice::class)]
		private UserDevice $result,
	)
	{
	}

	public function getResult(): UserDevice
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
