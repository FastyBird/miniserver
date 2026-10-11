<?php declare(strict_types = 1);

namespace FastyBird\Connector\Shelly\API\Messages\Response\Gen2;

use FastyBird\Connector\Shelly\API;
use FastyBird\Connector\Shelly\Types;
use Orisai\ObjectMapper;
use Override;

/**
 * Generation 2 device input configuration message
 */
final readonly class DeviceInputConfiguration implements API\Messages\Message
{

	public function __construct(
		#[ObjectMapper\Rules\IntValue(unsigned: true)]
		private int $id,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		private string|null $name,
		#[ObjectMapper\Rules\BackedEnumValue(class: Types\InputType::class)]
		private Types\InputType $type,
		#[ObjectMapper\Rules\BoolValue()]
		#[ObjectMapper\Modifiers\FieldName('invert')]
		private bool $inverted,
		#[ObjectMapper\Rules\BoolValue()]
		#[ObjectMapper\Modifiers\FieldName('factory_reset')]
		private bool $factoryReset,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\IntValue(),
			new ObjectMapper\Rules\NullValue(),
		])]
		#[ObjectMapper\Modifiers\FieldName('report_thr')]
		private int|null $reportThreshold,
	)
	{
	}

	public function getId(): int
	{
		return $this->id;
	}

	public function getType(): Types\ComponentType
	{
		return Types\ComponentType::INPUT;
	}

	public function getName(): string|null
	{
		return $this->name;
	}

	public function getInputType(): Types\InputType
	{
		return $this->type;
	}

	public function isInverted(): bool
	{
		return $this->inverted;
	}

	public function hasFactoryReset(): bool
	{
		return $this->factoryReset;
	}

	public function getReportThreshold(): int|null
	{
		return $this->reportThreshold;
	}

	/**
	 * {@inheritDoc}
	 */
	#[Override]
	public function toArray(): array
	{
		return [
			'id' => $this->getId(),
			'type' => $this->getType()->value,
			'name' => $this->getName(),
			'input_type' => $this->getInputType()->value,
			'inverted' => $this->isInverted(),
			'factory_reset' => $this->hasFactoryReset(),
			'report_threshold' => $this->getReportThreshold(),
		];
	}

}
