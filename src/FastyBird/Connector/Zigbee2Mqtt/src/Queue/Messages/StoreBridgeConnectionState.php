<?php declare(strict_types = 1);

namespace FastyBird\Connector\Zigbee2Mqtt\Queue\Messages;

use FastyBird\Connector\Zigbee2Mqtt\Types;
use Orisai\ObjectMapper;
use Ramsey\Uuid;
use function array_merge;

/**
 * Bridge connection state description message
 */
final class StoreBridgeConnectionState extends Bridge implements Message
{

	public function __construct(
		Uuid\UuidInterface $connector,
		string $baseTopic,
		#[ObjectMapper\Rules\BackedEnumValue(class: Types\ConnectionState::class)]
		private readonly Types\ConnectionState $state,
	)
	{
		parent::__construct($connector, $baseTopic);
	}

	public function getState(): Types\ConnectionState
	{
		return $this->state;
	}

	public function toArray(): array
	{
		return array_merge(
			parent::toArray(),
			[
				'state' => $this->getState()->value,
			],
		);
	}

}
