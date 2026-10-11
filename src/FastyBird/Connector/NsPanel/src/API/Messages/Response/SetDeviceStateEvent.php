<?php declare(strict_types = 1);

namespace FastyBird\Connector\NsPanel\API\Messages\Response;

use FastyBird\Connector\NsPanel\API;
use Orisai\ObjectMapper;
use Override;
use stdClass;

/**
 * Gateway report set device state event response definition
 */
final readonly class SetDeviceStateEvent implements API\Messages\Message
{

	public function __construct(
		#[ObjectMapper\Rules\MappedObjectValue(API\Messages\Header::class)]
		private API\Messages\Header $header,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\MappedObjectValue(SetDeviceStateEventPayload::class),
			new ObjectMapper\Rules\NullValue(),
		])]
		private SetDeviceStateEventPayload|null $payload = null,
	)
	{
	}

	public function getHeader(): API\Messages\Header
	{
		return $this->header;
	}

	public function getPayload(): SetDeviceStateEventPayload|null
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
			'payload' => $this->getPayload()?->toArray(),
		];
	}

	#[Override]
	public function toJson(): object
	{
		$json = new stdClass();
		$json->header = $this->getHeader()->toJson();
		$json->payload = $this->getPayload()?->toJson() ?? new stdClass();

		return $json;
	}

}
