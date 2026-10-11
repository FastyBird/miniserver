<?php declare(strict_types = 1);

namespace FastyBird\Bridge\ShellyConnectorHomeKitConnector\Mapping\Services;

use FastyBird\Bridge\ShellyConnectorHomeKitConnector\Mapping;
use FastyBird\Connector\HomeKit\Types as HomeKitTypes;
use Orisai\ObjectMapper;
use Override;
use TypeError;
use ValueError;
use function assert;
use function class_exists;

/**
 * Switch service mapping configuration
 */
readonly class SwitchService implements Service
{

	/**
	 * @param array<Mapping\Characteristics\Characteristic> $characteristics
	 */
	public function __construct(
		#[ObjectMapper\Rules\ArrayEnumValue(cases: [HomeKitTypes\ServiceType::SWITCH->value])]
		private string $type,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private string $class,
		#[ObjectMapper\Rules\BackedEnumValue(class: HomeKitTypes\AccessoryCategory::class)]
		private HomeKitTypes\AccessoryCategory $category,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		private string|null $channel = null,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\IntValue(castNumericString: true),
			new ObjectMapper\Rules\FloatValue(castNumericString: true),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		#[ObjectMapper\Modifiers\FieldName('index_start')]
		private int|null $indexStart = null,
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
	public function getCategory(): HomeKitTypes\AccessoryCategory
	{
		return $this->category;
	}

	#[Override]
	public function getChannel(): string|null
	{
		return $this->channel;
	}

	#[Override]
	public function getIndexStart(): int|null
	{
		return $this->indexStart;
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
