<?php declare(strict_types = 1);

namespace FastyBird\Connector\Zigbee2Mqtt\Queue\Messages;

use Orisai\ObjectMapper;
use Override;

/**
 * Coordinator information
 */
final readonly class CoordinatorInfo implements Message
{

	public function __construct(
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		#[ObjectMapper\Modifiers\FieldName('ieee_address')]
		private string $ieeeAddress,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private string $type,
	)
	{
	}

	public function getIeeeAddress(): string
	{
		return $this->ieeeAddress;
	}

	public function getType(): string
	{
		return $this->type;
	}

	#[Override]
	public function toArray(): array
	{
		return [
			'ieee_address' => $this->getIeeeAddress(),
			'type' => $this->getType(),
		];
	}

}
