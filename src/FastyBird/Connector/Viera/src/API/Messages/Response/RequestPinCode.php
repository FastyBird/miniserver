<?php declare(strict_types = 1);

namespace FastyBird\Connector\Viera\API\Messages\Response;

use FastyBird\Connector\Viera\API;
use Orisai\ObjectMapper;
use Override;

/**
 * Request pin code message
 */
readonly class RequestPinCode implements API\Messages\Message
{

	public function __construct(
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\NullValue(),
		])]
		#[ObjectMapper\Modifiers\FieldName('challenge_key')]
		private string|null $challengeKey = null,
	)
	{
	}

	public function getChallengeKey(): string|null
	{
		return $this->challengeKey;
	}

	/**
	 * {@inheritDoc}
	 */
	#[Override]
	public function toArray(): array
	{
		return [
			'challenge_key' => $this->getChallengeKey(),
		];
	}

}
