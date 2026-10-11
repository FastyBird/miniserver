<?php declare(strict_types = 1);

namespace FastyBird\Connector\NsPanel\API\Messages\Request;

use FastyBird\Connector\NsPanel\API;
use Orisai\ObjectMapper;
use Override;
use stdClass;

/**
 * NS Panel requested set device state directive request definition
 */
final readonly class SetDeviceStateDirective implements API\Messages\Message
{

	public function __construct(
		#[ObjectMapper\Rules\MappedObjectValue(API\Messages\Header::class)]
		private API\Messages\Header $header,
		#[ObjectMapper\Rules\MappedObjectValue(SetDeviceStateDirectiveEndpoint::class)]
		private SetDeviceStateDirectiveEndpoint $endpoint,
		#[ObjectMapper\Rules\MappedObjectValue(SetDeviceStateDirectivePayload::class)]
		private SetDeviceStateDirectivePayload $payload,
	)
	{
	}

	public function getHeader(): API\Messages\Header
	{
		return $this->header;
	}

	public function getEndpoint(): SetDeviceStateDirectiveEndpoint
	{
		return $this->endpoint;
	}

	public function getPayload(): SetDeviceStateDirectivePayload
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
			'endpoint' => $this->getEndpoint()->toArray(),
			'payload' => $this->getPayload()->toArray(),
		];
	}

	#[Override]
	public function toJson(): object
	{
		$json = new stdClass();
		$json->header = $this->getHeader()->toJson();
		$json->endpoint = $this->getEndpoint()->toJson();
		$json->payload = $this->getPayload()->toJson();

		return $json;
	}

}
