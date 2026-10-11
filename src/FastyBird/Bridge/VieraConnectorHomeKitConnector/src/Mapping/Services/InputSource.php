<?php declare(strict_types = 1);

namespace FastyBird\Bridge\VieraConnectorHomeKitConnector\Mapping\Services;

use FastyBird\Bridge\VieraConnectorHomeKitConnector\Mapping;
use FastyBird\Connector\HomeKit\Types as HomeKitTypes;
use Orisai\ObjectMapper;
use Override;
use TypeError;
use ValueError;
use function assert;
use function class_exists;

/**
 * Input source service mapping configuration
 */
readonly class InputSource implements Service
{

	/**
	 * @param array<Mapping\Characteristics\Characteristic> $characteristics
	 */
	public function __construct(
		#[ObjectMapper\Rules\ArrayEnumValue(cases: [HomeKitTypes\ServiceType::INPUT_SOURCE->value])]
		private string $type,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private string $class,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		private string|null $channel = null,
		#[ObjectMapper\Rules\BoolValue(castBoolLike: true)]
		private bool $multiple = false,
		#[ObjectMapper\Rules\ArrayOf(
			new ObjectMapper\Rules\MappedObjectValue(class: Mapping\Characteristics\BasicCharacteristic::class),
			new ObjectMapper\Rules\IntValue(unsigned: true),
		)]
		private array $characteristics = [],
	)
	{
		assert(class_exists($this->class));
	}

	/**
	 * @throws TypeError
	 * @throws ValueError
	 */
	#[Override]
	public function getType(): HomeKitTypes\ServiceType
	{
		return HomeKitTypes\ServiceType::from($this->type);
	}

	#[Override]
	public function getClass(): string
	{
		assert(class_exists($this->class));

		return $this->class;
	}

	#[Override]
	public function getChannel(): string|null
	{
		return $this->channel;
	}

	#[Override]
	public function isMultiple(): bool
	{
		return $this->multiple;
	}

	/**
	 * @return array<Mapping\Characteristics\Characteristic>
	 */
	#[Override]
	public function getCharacteristics(): array
	{
		return $this->characteristics;
	}

}
