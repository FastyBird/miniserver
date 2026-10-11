<?php declare(strict_types = 1);

namespace FastyBird\Connector\Shelly\API\Messages\Response\Gen2;

use FastyBird\Connector\Shelly\API;
use FastyBird\Connector\Shelly\Types;
use Orisai\ObjectMapper;
use Override;

/**
 * Generation 2 device input configuration message
 */
final readonly class DeviceCoverConfiguration implements API\Messages\Message
{

	public function __construct(
		#[ObjectMapper\Rules\IntValue(unsigned: true)]
		private int $id,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		private string|null $name,
		#[ObjectMapper\Rules\ArrayEnumValue(cases: ['single', 'dual', 'detached'])]
		#[ObjectMapper\Modifiers\FieldName('in_mode')]
		private string $mode,
		#[ObjectMapper\Rules\ArrayEnumValue(cases: ['open', 'closed', 'stopped'])]
		#[ObjectMapper\Modifiers\FieldName('initial_state')]
		private string $initialState,
		#[ObjectMapper\Rules\FloatValue()]
		#[ObjectMapper\Modifiers\FieldName('power_limit')]
		private float $powerLimit,
		#[ObjectMapper\Rules\FloatValue()]
		#[ObjectMapper\Modifiers\FieldName('voltage_limit')]
		private float $voltageLimit,
		#[ObjectMapper\Rules\FloatValue()]
		#[ObjectMapper\Modifiers\FieldName('current_limit')]
		private float $currentLimit,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\MappedObjectValue(class: CoverMotorConfigurationBlock::class),
			new ObjectMapper\Rules\NullValue(),
		])]
		private CoverMotorConfigurationBlock|null $motor,
		#[ObjectMapper\Rules\FloatValue()]
		#[ObjectMapper\Modifiers\FieldName('maxtime_open')]
		private float $maximumOpeningTime,
		#[ObjectMapper\Rules\FloatValue()]
		#[ObjectMapper\Modifiers\FieldName('maxtime_close')]
		private float $maximumClosingTime,
		#[ObjectMapper\Rules\BoolValue()]
		#[ObjectMapper\Modifiers\FieldName('swap_inputs')]
		private bool $swappedInput,
		#[ObjectMapper\Rules\BoolValue()]
		#[ObjectMapper\Modifiers\FieldName('invert_directions')]
		private bool $invertedDirections,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\MappedObjectValue(class: CoverObstructionDetectionConfigurationBlock::class),
			new ObjectMapper\Rules\NullValue(),
		])]
		#[ObjectMapper\Modifiers\FieldName('obstruction_detection')]
		private CoverObstructionDetectionConfigurationBlock|null $obstructionDetection,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\MappedObjectValue(class: CoverSafetySwitchConfigurationBlock::class),
			new ObjectMapper\Rules\NullValue(),
		])]
		#[ObjectMapper\Modifiers\FieldName('safety_switch')]
		private CoverSafetySwitchConfigurationBlock|null $safetySwitch,
	)
	{
	}

	public function getId(): int
	{
		return $this->id;
	}

	public function getType(): Types\ComponentType
	{
		return Types\ComponentType::COVER;
	}

	public function getName(): string|null
	{
		return $this->name;
	}

	public function getMode(): string
	{
		return $this->mode;
	}

	public function getInitialState(): string
	{
		return $this->initialState;
	}

	public function getPowerLimit(): float
	{
		return $this->powerLimit;
	}

	public function getVoltageLimit(): float
	{
		return $this->voltageLimit;
	}

	public function getCurrentLimit(): float
	{
		return $this->currentLimit;
	}

	public function getMaximumOpeningTime(): float
	{
		return $this->maximumOpeningTime;
	}

	public function getMaximumClosingTime(): float
	{
		return $this->maximumClosingTime;
	}

	public function hasSwappedInputs(): bool
	{
		return $this->swappedInput;
	}

	public function hasInvertedDirection(): bool
	{
		return $this->invertedDirections;
	}

	public function getMotor(): CoverMotorConfigurationBlock|null
	{
		return $this->motor;
	}

	public function getObstructionDetection(): CoverObstructionDetectionConfigurationBlock|null
	{
		return $this->obstructionDetection;
	}

	public function getSafetySwitch(): CoverSafetySwitchConfigurationBlock|null
	{
		return $this->safetySwitch;
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
			'mode' => $this->getMode(),
			'initial_state' => $this->getInitialState(),
			'power_limit' => $this->getPowerLimit(),
			'voltage_limit' => $this->getVoltageLimit(),
			'current_limit' => $this->getCurrentLimit(),
			'motor' => $this->getMotor()?->toArray(),
			'maximum_opening_time' => $this->getMaximumOpeningTime(),
			'maximum_closing_time' => $this->getMaximumClosingTime(),
			'swapped_input' => $this->hasSwappedInputs(),
			'inverted_directions' => $this->hasInvertedDirection(),
			'obstruction_detection' => $this->getObstructionDetection()?->toArray(),
			'safety_switch' => $this->getSafetySwitch()?->toArray(),
		];
	}

}
