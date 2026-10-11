<?php declare(strict_types = 1);

namespace FastyBird\Connector\NsPanel\API\Messages\Response;

use FastyBird\Connector\NsPanel\API;
use Orisai\ObjectMapper;
use Override;
use stdClass;

/**
 * NS Panel event error response definition
 */
final readonly class ErrorEvent implements API\Messages\Message
{

	public function __construct(
		#[ObjectMapper\Rules\MappedObjectValue(API\Messages\Header::class)]
		private API\Messages\Header $header,
		#[ObjectMapper\Rules\MappedObjectValue(ErrorEventPayload::class)]
		private ErrorEventPayload $payload,
	)
	{
	}

	public function getHeader(): API\Messages\Header
	{
		return $this->header;
	}

	public function getPayload(): ErrorEventPayload
	{
		return $this->payload;
	}

	/**
	 * {@inheritDoc}
	 */
	#[Override]
	public function toArray(): array
	{
		return [
			'header' => $this->getHeader()->toArray(),
			'payload' => $this->getPayload()->toArray(),
		];
	}

	#[Override]
	public function toJson(): object
	{
		$json = new stdClass();
		$json->header = $this->getHeader()->toJson();
		$json->payload = $this->getPayload()->toJson();

		return $json;
	}

}
