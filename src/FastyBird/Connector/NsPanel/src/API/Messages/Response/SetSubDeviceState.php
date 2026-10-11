<?php declare(strict_types = 1);

namespace FastyBird\Connector\NsPanel\API\Messages\Response;

use FastyBird\Connector\NsPanel\API;
use Orisai\ObjectMapper;
use Override;
use stdClass;

/**
 * Set NS Panel sub-device state response definition
 */
final readonly class SetSubDeviceState implements API\Messages\Message
{

	public function __construct(
		#[ObjectMapper\Rules\IntValue(unsigned: true)]
		private int $error,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private string $message,
	)
	{
	}

	public function getError(): int
	{
		return $this->error;
	}

	public function getMessage(): string
	{
		return $this->message;
	}

	/**
	 * {@inheritDoc}
	 */
	#[Override]
	public function toArray(): array
	{
		return [
			'error' => $this->getError(),
			'data' => [],
			'message' => $this->getMessage(),
		];
	}

	#[Override]
	public function toJson(): object
	{
		$json = new stdClass();
		$json->error = $this->getError();
		$json->data = new stdClass();
		$json->message = $this->getMessage();

		return $json;
	}

}
