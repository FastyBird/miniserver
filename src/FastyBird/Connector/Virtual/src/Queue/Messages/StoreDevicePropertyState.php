<?php declare(strict_types = 1);

/**
 * StoreDevicePropertyState.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:VirtualConnector!
 * @subpackage     Queue
 * @since          1.0.0
 *
 * @date           22.11.22
 */

namespace FastyBird\Connector\Virtual\Queue\Messages;

use FastyBird\Core\Application\ObjectMapper as ApplicationObjectMapper;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use Orisai\ObjectMapper;
use Ramsey\Uuid;
use function is_string;

/**
 * Device status message
 *
 * @package        FastyBird:VirtualConnector!
 * @subpackage     Queue
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final readonly class StoreDevicePropertyState implements Message
{

	public function __construct(
		#[ApplicationObjectMapper\Rules\UuidValue()]
		private Uuid\UuidInterface $connector,
		#[ApplicationObjectMapper\Rules\UuidValue()]
		private Uuid\UuidInterface $device,
		#[ObjectMapper\Rules\AnyOf([
			new ApplicationObjectMapper\Rules\UuidValue(),
			new ObjectMapper\Rules\StringValue(notEmpty: true),
		])]
		private Uuid\UuidInterface|string $property,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\FloatValue(),
			new ObjectMapper\Rules\IntValue(),
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\BoolValue(),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		private float|int|string|bool|null $value,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\InstanceOfValue(type: MetadataTypes\Sources\Connector::class),
			new ObjectMapper\Rules\InstanceOfValue(type: MetadataTypes\Sources\Addon::class),
		])]
		private MetadataTypes\Sources\Source $source,
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

	/**
	 * @throws Uuid\Exception\InvalidArgumentException
	 */
	public function getProperty(): Uuid\UuidInterface|string
	{
		if (is_string($this->property) && Uuid\Uuid::isValid($this->property)) {
			return Uuid\Uuid::fromString($this->property);
		}

		return $this->property;
	}

	public function getValue(): float|bool|int|string|null
	{
		return $this->value;
	}

	public function getSource(): MetadataTypes\Sources\Source
	{
		return $this->source;
	}

	/**
	 * {@inheritDoc}
	 * @throws Uuid\Exception\InvalidArgumentException
	 */
	public function toArray(): array
	{
		return [
			'connector' => $this->getConnector()->toString(),
			'device' => $this->getDevice()->toString(),
			'property' => is_string($this->getProperty()) ? $this->getProperty() : $this->getProperty()->toString(),
			'value' => $this->getValue(),
			'source' => $this->getSource()->value,
		];
	}

}
