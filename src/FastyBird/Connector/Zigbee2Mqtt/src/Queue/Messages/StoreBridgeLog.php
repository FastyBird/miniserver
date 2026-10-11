<?php declare(strict_types = 1);

namespace FastyBird\Connector\Zigbee2Mqtt\Queue\Messages;

use Orisai\ObjectMapper;
use Ramsey\Uuid;
use function array_merge;

/**
 * Bridge connection state description message
 */
final class StoreBridgeLog extends Bridge implements Message
{

	public function __construct(
		Uuid\UuidInterface $connector,
		string $baseTopic,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private readonly string $level,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private readonly string $message,
	)
	{
		parent::__construct($connector, $baseTopic);
	}

	public function getLevel(): string
	{
		return $this->level;
	}

	public function getMessage(): string
	{
		return $this->message;
	}

	public function toArray(): array
	{
		return array_merge(
			parent::toArray(),
			[
				'level' => $this->getLevel(),
				'message' => $this->getMessage(),
			],
		);
	}

}
