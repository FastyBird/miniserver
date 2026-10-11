<?php declare(strict_types = 1);

namespace FastyBird\Connector\HomeKit\Queue\Messages;

use FastyBird\Core\Persistence\Rules;
use Orisai\ObjectMapper;
use Override;
use Ramsey\Uuid;

/**
 * Device status message
 */
final readonly class StoreDevicePropertyState implements Message
{

	public function __construct(
		#[Rules\UuidValue()]
		private Uuid\UuidInterface $connector,
		#[Rules\UuidValue()]
		private Uuid\UuidInterface $device,
		#[Rules\UuidValue()]
		private Uuid\UuidInterface $property,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\FloatValue(),
			new ObjectMapper\Rules\IntValue(),
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\BoolValue(),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		private float|int|string|bool|null $value,
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

	public function getProperty(): Uuid\UuidInterface
	{
		return $this->property;
	}

	public function getValue(): float|bool|int|string|null
	{
		return $this->value;
	}

	/**
	 * {@inheritDoc}
	 */
	#[Override]
	public function toArray(): array
	{
		return [
			'connector' => $this->getConnector()->toString(),
			'device' => $this->getDevice()->toString(),
			'property' => $this->getProperty()->toString(),
			'value' => $this->getValue(),
		];
	}

}
