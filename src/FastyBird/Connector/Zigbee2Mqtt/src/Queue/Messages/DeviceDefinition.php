<?php declare(strict_types = 1);

namespace FastyBird\Connector\Zigbee2Mqtt\Queue\Messages;

use FastyBird\Connector\Zigbee2Mqtt\Exceptions;
use Orisai\ObjectMapper;
use Override;
use TypeError;
use ValueError;
use function array_map;

/**
 * Device definition message
 */
final readonly class DeviceDefinition implements Message
{

	/**
	 * @param array<Exposes\Type> $exposes
	 */
	public function __construct(
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		private string|null $model,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		private string|null $vendor,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		private string|null $description,
		#[ObjectMapper\Rules\ArrayOf(
			new ObjectMapper\Rules\AnyOf([
				new ObjectMapper\Rules\MappedObjectValue(class: Exposes\BinaryType::class),
				new ObjectMapper\Rules\MappedObjectValue(class: Exposes\EnumType::class),
				new ObjectMapper\Rules\MappedObjectValue(class: Exposes\NumericType::class),
				new ObjectMapper\Rules\MappedObjectValue(class: Exposes\TextType::class),
				new ObjectMapper\Rules\MappedObjectValue(class: Exposes\CompositeType::class),
				new ObjectMapper\Rules\MappedObjectValue(class: Exposes\ListType::class),
				new ObjectMapper\Rules\MappedObjectValue(class: Exposes\ClimateType::class),
				new ObjectMapper\Rules\MappedObjectValue(class: Exposes\CoverType::class),
				new ObjectMapper\Rules\MappedObjectValue(class: Exposes\FanType::class),
				new ObjectMapper\Rules\MappedObjectValue(class: Exposes\LightType::class),
				new ObjectMapper\Rules\MappedObjectValue(class: Exposes\LockType::class),
				new ObjectMapper\Rules\MappedObjectValue(class: Exposes\SwitchType::class),
			]),
			new ObjectMapper\Rules\IntValue(unsigned: true),
		)]
		private array $exposes,
		#[ObjectMapper\Rules\BoolValue()]
		#[ObjectMapper\Modifiers\FieldName('supports_ota')]
		private bool $supportsOta,
	)
	{
	}

	public function getModel(): string|null
	{
		return $this->model;
	}

	public function getVendor(): string|null
	{
		return $this->vendor;
	}

	public function getDescription(): string|null
	{
		return $this->description;
	}

	/**
	 * @return array<Exposes\Type>
	 */
	public function getExposes(): array
	{
		return $this->exposes;
	}

	public function doesSupportsOta(): bool
	{
		return $this->supportsOta;
	}

	/**
	 * @throws Exceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	#[Override]
	public function toArray(): array
	{
		return [
			'model' => $this->getModel(),
			'vendor' => $this->getVendor(),
			'description' => $this->getDescription(),
			'exposes' => array_map(
				static fn (Exposes\Type $expose): array => $expose->toArray(),
				$this->getExposes(),
			),
			'supports_ota' => $this->doesSupportsOta(),
		];
	}

}
