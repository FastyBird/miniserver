<?php declare(strict_types = 1);

namespace FastyBird\Connector\NsPanel\API\Messages\Response;

use FastyBird\Connector\NsPanel\API;
use Orisai\ObjectMapper;
use Override;
use stdClass;
use function array_map;

/**
 * Synchronise third-party devices with NS Panel even payload response definition
 */
final readonly class SyncDevicesPayload implements API\Messages\Message
{

	/**
	 * @param array<SyncDevicesPayloadEndpoint> $endpoints
	 */
	public function __construct(
		#[ObjectMapper\Rules\ArrayOf(
			new ObjectMapper\Rules\MappedObjectValue(SyncDevicesPayloadEndpoint::class),
		)]
		private array $endpoints,
	)
	{
	}

	/**
	 * @return array<SyncDevicesPayloadEndpoint>
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
				static fn (SyncDevicesPayloadEndpoint $description): array => $description->toArray(),
				$this->getEndpoints(),
			),
		];
	}

	#[Override]
	public function toJson(): object
	{
		$json = new stdClass();
		$json->endpoints = array_map(
			static fn (SyncDevicesPayloadEndpoint $description): object => $description->toJson(),
			$this->getEndpoints(),
		);

		return $json;
	}

}
