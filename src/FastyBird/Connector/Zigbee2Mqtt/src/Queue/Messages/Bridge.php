<?php declare(strict_types = 1);

namespace FastyBird\Connector\Zigbee2Mqtt\Queue\Messages;

use FastyBird\Core\Persistence\Rules;
use Orisai\ObjectMapper;
use Ramsey\Uuid;

/**
 * Bridge message
 */
abstract class Bridge implements Message
{

	public function __construct(
		#[Rules\UuidValue()]
		private readonly Uuid\UuidInterface $connector,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		#[ObjectMapper\Modifiers\FieldName('base_topic')]
		private readonly string $baseTopic,
	)
	{
	}

	public function getConnector(): Uuid\UuidInterface
	{
		return $this->connector;
	}

	public function getBaseTopic(): string
	{
		return $this->baseTopic;
	}

	public function toArray(): array
	{
		return [
			'connector' => $this->getConnector()->toString(),
			'base_topic' => $this->getBaseTopic(),
		];
	}

}
