<?php declare(strict_types = 1);

namespace FastyBird\Connector\Zigbee2Mqtt\Queue\Messages;

use Orisai\ObjectMapper;
use Override;

/**
 * Bridge group member description message
 */
final readonly class BridgeGroupMember implements Message
{

	public function __construct(
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		#[ObjectMapper\Modifiers\FieldName('ieee_address')]
		private string $ieeeAddress,
		#[ObjectMapper\Rules\IntValue(unsigned: true)]
		private int $endpoint,
	)
	{
	}

	public function getIeeeAddress(): string
	{
		return $this->ieeeAddress;
	}

	public function getEndpoint(): int
	{
		return $this->endpoint;
	}

	#[Override]
	public function toArray(): array
	{
		return [
			'ieee_address' => $this->getIeeeAddress(),
			'endpoint' => $this->getEndpoint(),
		];
	}

}
