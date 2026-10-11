<?php declare(strict_types = 1);

namespace FastyBird\Connector\Tuya\API\Messages\Response;

use FastyBird\Connector\Tuya\API;
use Orisai\ObjectMapper;
use Override;

/**
 * OpenAPI refresh access token message
 */
final readonly class RefreshAccessToken implements API\Messages\Message
{

	public function __construct(
		#[ObjectMapper\Rules\MappedObjectValue(AccessToken::class)]
		private AccessToken $result,
	)
	{
	}

	public function getResult(): AccessToken
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
