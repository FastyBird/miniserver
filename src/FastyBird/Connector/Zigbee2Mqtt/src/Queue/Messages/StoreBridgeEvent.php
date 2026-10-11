<?php declare(strict_types = 1);

namespace FastyBird\Connector\Zigbee2Mqtt\Queue\Messages;

use FastyBird\Connector\Zigbee2Mqtt\Types;
use Orisai\ObjectMapper;
use Ramsey\Uuid;
use function array_merge;

/**
 * Bridge event message
 */
final class StoreBridgeEvent extends Bridge implements Message
{

	public function __construct(
		Uuid\UuidInterface $connector,
		string $baseTopic,
		#[ObjectMapper\Rules\BackedEnumValue(class: Types\BridgeEvent::class)]
		private readonly Types\BridgeEvent $type,
		#[ObjectMapper\Rules\MappedObjectValue(EventData::class)]
		private readonly EventData $data,
	)
	{
		parent::__construct($connector, $baseTopic);
	}

	public function getType(): Types\BridgeEvent
	{
		return $this->type;
	}

	public function getData(): EventData
	{
		return $this->data;
	}

	public function toArray(): array
	{
		return array_merge(
			parent::toArray(),
			[
				'type' => $this->getType()->value,
				'data' => $this->getData()->toArray(),
			],
		);
	}

}
