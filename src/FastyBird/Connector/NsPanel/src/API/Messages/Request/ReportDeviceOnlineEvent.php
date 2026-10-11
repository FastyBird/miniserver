<?php declare(strict_types = 1);

namespace FastyBird\Connector\NsPanel\API\Messages\Request;

use FastyBird\Connector\NsPanel\API;
use Orisai\ObjectMapper;
use Override;
use stdClass;

/**
 * Report third-party device online state to NS Panel event request definition
 */
final readonly class ReportDeviceOnlineEvent implements API\Messages\Message
{

	public function __construct(
		#[ObjectMapper\Rules\MappedObjectValue(API\Messages\Header::class)]
		private API\Messages\Header $header,
		#[ObjectMapper\Rules\MappedObjectValue(ReportDeviceOnlineEventEndpoint::class)]
		private ReportDeviceOnlineEventEndpoint $endpoint,
		#[ObjectMapper\Rules\MappedObjectValue(ReportDeviceOnlineEventPayload::class)]
		private ReportDeviceOnlineEventPayload $payload,
	)
	{
	}

	public function getHeader(): API\Messages\Header
	{
		return $this->header;
	}

	public function getEndpoint(): ReportDeviceOnlineEventEndpoint
	{
		return $this->endpoint;
	}

	public function getPayload(): ReportDeviceOnlineEventPayload
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
