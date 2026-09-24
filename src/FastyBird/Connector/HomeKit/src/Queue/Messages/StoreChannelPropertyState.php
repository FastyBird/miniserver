<?php declare(strict_types = 1);

/**
 * StoreChannelPropertyState.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:HomeKitConnector!
 * @subpackage     Queue
 * @since          1.0.0
 *
 * @date           30.11.23
 */

namespace FastyBird\Connector\HomeKit\Queue\Messages;

use FastyBird\Core\Persistence\Application\Rules as ApplicationObjectMapper;
use FastyBird\Core\Values\Types\Payloads;
use FastyBird\Core\Values\Utilities;
use Orisai\ObjectMapper;
use Ramsey\Uuid;

/**
 * Device status message
 *
 * @package        FastyBird:HomeKitConnector!
 * @subpackage     Queue
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final readonly class StoreChannelPropertyState implements Message
{

	public function __construct(
		#[ApplicationObjectMapper\UuidValue()]
		private Uuid\UuidInterface $connector,
		#[ApplicationObjectMapper\UuidValue()]
		private Uuid\UuidInterface $device,
		#[ApplicationObjectMapper\UuidValue()]
		private Uuid\UuidInterface $channel,
		#[ApplicationObjectMapper\UuidValue()]
		private Uuid\UuidInterface $property,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\FloatValue(),
			new ObjectMapper\Rules\IntValue(),
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\BoolValue(),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
			new ObjectMapper\Rules\BackedEnumValue(class: Payloads\Button::class),
			new ObjectMapper\Rules\BackedEnumValue(class: Payloads\Switcher::class),
			new ObjectMapper\Rules\BackedEnumValue(class: Payloads\Cover::class),
		])]
		private float|int|string|bool|Payloads\Payload|null $value,
	)
	{
	}

	public function getConnector(): Uuid\UuidInterface
	{
		return $this->connector;
	}

	public function getDevice(): Uuid\UuidInterface
	{
		return $this->device;
	}

	public function getChannel(): Uuid\UuidInterface
	{
		return $this->channel;
	}

	public function getProperty(): Uuid\UuidInterface
	{
		return $this->property;
	}

	public function getValue(): float|int|string|bool|Payloads\Payload|null
	{
		return $this->value;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'connector' => $this->getConnector()->toString(),
			'device' => $this->getDevice()->toString(),
			'channel' => $this->getChannel()->toString(),
			'property' => $this->getProperty()->toString(),
			'value' => Utilities\Value::flattenValue($this->getValue()),
		];
	}

}
