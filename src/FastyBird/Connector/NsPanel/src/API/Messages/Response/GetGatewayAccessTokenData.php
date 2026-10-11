<?php declare(strict_types = 1);

namespace FastyBird\Connector\NsPanel\API\Messages\Response;

use FastyBird\Connector\NsPanel\API;
use Orisai\ObjectMapper;
use stdClass;

/**
 * NS Panel acquire access token data response definition
 */
final readonly class GetGatewayAccessTokenData implements API\Messages\Message
{

	public function __construct(
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private string $token,
	)
	{
	}

	public function getAccessToken(): string
	{
		return $this->token;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'access_token' => $this->getAccessToken(),
		];
	}

	public function toJson(): object
	{
		$json = new stdClass();
		$json->token = $this->getAccessToken();

		return $json;
	}

}
