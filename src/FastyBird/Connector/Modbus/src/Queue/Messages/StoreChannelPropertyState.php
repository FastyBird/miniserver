<?php declare(strict_types = 1);

namespace FastyBird\Connector\Modbus\Queue\Messages;

use FastyBird\Core\Persistence\Rules;
use Orisai\ObjectMapper;
use Override;
use Ramsey\Uuid;
use function array_merge;

/**
 * Device status message
 */
final class StoreChannelPropertyState extends Device
{

	public function __construct(
		Uuid\UuidInterface $connector,
		Uuid\UuidInterface $device,
		#[Rules\UuidValue()]
		private readonly Uuid\UuidInterface $channel,
		#[Rules\UuidValue()]
		private readonly Uuid\UuidInterface $property,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\BoolValue(),
			new ObjectMapper\Rules\FloatValue(),
			new ObjectMapper\Rules\IntValue(),
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		private readonly bool|float|int|string|null $value,
	)
	{
		parent::__construct($connector, $device);
	}

	public function getChannel(): Uuid\UuidInterface
	{
		return $this->channel;
	}

	public function getProperty(): Uuid\UuidInterface
	{
		return $this->property;
	}

	public function getValue(): bool|float|int|string|null
	{
		return $this->value;
	}

	/**
	 * {@inheritDoc}
	 */
	#[Override]
	public function toArray(): array
	{
		return array_merge(parent::toArray(), [
			'channel' => $this->getChannel()->toString(),
			'property' => $this->getProperty()->toString(),
			'value' => $this->getValue(),
		]);
	}

}
