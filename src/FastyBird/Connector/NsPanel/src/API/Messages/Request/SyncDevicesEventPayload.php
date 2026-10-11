<?php declare(strict_types = 1);

namespace FastyBird\Connector\NsPanel\API\Messages\Request;

use FastyBird\Connector\NsPanel\API;
use Orisai\ObjectMapper;
use Override;
use stdClass;
use function array_map;

/**
 * Synchronise third-party devices with NS Panel event payload request definition
 */
final readonly class SyncDevicesEventPayload implements API\Messages\Message
{

	/**
	 * @param array<SyncDevicesEventPayloadEndpoint> $endpoints
	 */
	public function __construct(
		#[ObjectMapper\Rules\ArrayOf(
			new ObjectMapper\Rules\MappedObjectValue(SyncDevicesEventPayloadEndpoint::class),
		)]
		private array $endpoints,
	)
	{
	}

	/**
	 * @return array<SyncDevicesEventPayloadEndpoint>
	 */
	public function getEndpoints(): array
	{
		return $this->endpoints;
	}

	/**
	 * {@inheritDoc}
	 */
	#[Override]
	public function toArray(): array
	{
		return [
			'endpoints' => array_map(
				static fn (SyncDevicesEventPayloadEndpoint $description): array => $description->toArray(),
				$this->getEndpoints(),
			),
		];
	}

	#[Override]
	public function toJson(): object
	{
		$json = new stdClass();
		$json->endpoints = array_map(
			static fn (SyncDevicesEventPayloadEndpoint $endpoint): object => $endpoint->toJson(),
			$this->getEndpoints(),
		);

		return $json;
	}

}
