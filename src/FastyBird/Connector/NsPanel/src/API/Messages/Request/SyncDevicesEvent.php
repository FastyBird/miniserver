<?php declare(strict_types = 1);

namespace FastyBird\Connector\NsPanel\API\Messages\Request;

use FastyBird\Connector\NsPanel\API;
use Orisai\ObjectMapper;
use Override;
use stdClass;

/**
 * Synchronise third-party devices with NS Panel event request definition
 */
final readonly class SyncDevicesEvent implements API\Messages\Message
{

	public function __construct(
		#[ObjectMapper\Rules\MappedObjectValue(API\Messages\Header::class)]
		private API\Messages\Header $header,
		#[ObjectMapper\Rules\MappedObjectValue(SyncDevicesEventPayload::class)]
		private SyncDevicesEventPayload $payload,
	)
	{
	}

	public function getHeader(): API\Messages\Header
	{
		return $this->header;
	}

	public function getPayload(): SyncDevicesEventPayload
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
