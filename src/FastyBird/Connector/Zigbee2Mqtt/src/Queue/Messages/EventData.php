<?php declare(strict_types = 1);

namespace FastyBird\Connector\Zigbee2Mqtt\Queue\Messages;

use FastyBird\Connector\Zigbee2Mqtt\Types;
use Orisai\ObjectMapper;
use Override;
use function array_merge;

/**
 * Event data message
 */
final readonly class EventData implements Message
{

	public function __construct(
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		#[ObjectMapper\Modifiers\FieldName('friendly_name')]
		private string $friendlyName,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		#[ObjectMapper\Modifiers\FieldName('ieee_address')]
		private string $ieeeAddress,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\BackedEnumValue(class: Types\EventStatus::class),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		private Types\EventStatus|null $status = null,
		#[ObjectMapper\Rules\BoolValue()]
		private bool|null $supported = null,
	)
	{
	}

	public function getFriendlyName(): string
	{
		return $this->friendlyName;
	}

	public function getIeeeAddress(): string
	{
		return $this->ieeeAddress;
	}

	public function getStatus(): Types\EventStatus|null
	{
		return $this->status;
	}

	public function getSupported(): bool|null
	{
		return $this->supported;
	}

	#[Override]
	public function toArray(): array
	{
		return array_merge(
			[
				'friendly_name' => $this->getFriendlyName(),
				'ieee_address' => $this->getIeeeAddress(),
			],
			$this->getStatus() !== null
				? ['status' => $this->getStatus()->value]
				: [],
			$this->getStatus() !== null && $this->getStatus() === Types\EventStatus::SUCCESSFUL
				? ['supported' => $this->getSupported()]
				: [],
		);
	}

}
