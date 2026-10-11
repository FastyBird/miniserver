<?php declare(strict_types = 1);

namespace FastyBird\Connector\Tuya\API\Messages\Response;

use FastyBird\Connector\Tuya\API;
use Orisai\ObjectMapper;
use Override;

/**
 * OpenAPI get user device specifications message
 */
final readonly class GetUserDeviceSpecifications implements API\Messages\Message
{

	public function __construct(
		#[ObjectMapper\Rules\MappedObjectValue(UserDeviceSpecifications::class)]
		private UserDeviceSpecifications $result,
	)
	{
	}

	public function getResult(): UserDeviceSpecifications
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
