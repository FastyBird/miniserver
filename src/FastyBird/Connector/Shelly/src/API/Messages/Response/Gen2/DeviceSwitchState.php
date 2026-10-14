<?php declare(strict_types = 1);

namespace FastyBird\Connector\Shelly\API\Messages\Response\Gen2;

use DateTimeInterface;
use Exception;
use FastyBird\Connector\Shelly\API;
use FastyBird\Connector\Shelly\Types;
use Nette\Utils;
use Orisai\ObjectMapper;
use Override;
use function array_filter;
use function array_merge;
use function intval;
use function is_float;

/**
 * Generation 2 device switch state message
 */
final class DeviceSwitchState extends DeviceState implements API\Messages\Message
{

	/**
	 * @param array<string> $errors
	 */
	public function __construct(
		int $id,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		private readonly string|null $source,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\BoolValue(),
			new ObjectMapper\Rules\ArrayEnumValue(cases: [DeviceState::VALUE_NOT_AVAILABLE]),
		])]
		private readonly bool|string $output,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\FloatValue(),
			new ObjectMapper\Rules\ArrayEnumValue(cases: [DeviceState::VALUE_NOT_AVAILABLE]),
		])]
		#[ObjectMapper\Modifiers\FieldName('timer_started_at')]
		private readonly float|string $timerStartedAt,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\IntValue(),
			new ObjectMapper\Rules\ArrayEnumValue(cases: [DeviceState::VALUE_NOT_AVAILABLE]),
		])]
		#[ObjectMapper\Modifiers\FieldName('timer_duration')]
		private readonly float|string $timerDuration,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\FloatValue(),
			new ObjectMapper\Rules\ArrayEnumValue(cases: [DeviceState::VALUE_NOT_AVAILABLE]),
		])]
		#[ObjectMapper\Modifiers\FieldName('apower')]
		private readonly float|string $activePower,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\FloatValue(),
			new ObjectMapper\Rules\ArrayEnumValue(cases: [DeviceState::VALUE_NOT_AVAILABLE]),
		])]
		private readonly float|string $voltage,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\FloatValue(),
			new ObjectMapper\Rules\ArrayEnumValue(cases: [DeviceState::VALUE_NOT_AVAILABLE]),
		])]
		private readonly float|string $current,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\FloatValue(),
			new ObjectMapper\Rules\ArrayEnumValue(cases: [DeviceState::VALUE_NOT_AVAILABLE]),
		])]
		#[ObjectMapper\Modifiers\FieldName('pf')]
		private readonly float|string $powerFactor,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\FloatValue(),
			new ObjectMapper\Rules\ArrayEnumValue(cases: [DeviceState::VALUE_NOT_AVAILABLE]),
		])]
		#[ObjectMapper\Modifiers\FieldName('freq')]
		private readonly float|string $frequency,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\MappedObjectValue(class: ActiveEnergyStateBlock::class),
			new ObjectMapper\Rules\ArrayEnumValue(cases: [DeviceState::VALUE_NOT_AVAILABLE]),
		])]
		#[ObjectMapper\Modifiers\FieldName('aenergy')]
		private readonly ActiveEnergyStateBlock|string $activeEnergy,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\MappedObjectValue(class: TemperatureBlockState::class),
			new ObjectMapper\Rules\ArrayEnumValue(cases: [DeviceState::VALUE_NOT_AVAILABLE]),
		])]
		private readonly TemperatureBlockState|string $temperature,
		array $errors = [],
	)
	{
		parent::__construct($id, $errors);
	}

	#[Override]
	public function getType(): Types\ComponentType
	{
		return Types\ComponentType::SWITCH;
	}

	public function getSource(): string|null
	{
		return $this->source;
	}

	public function getOutput(): bool|string
	{
		return $this->output;
	}

	/**
	 * @throws Exception
	 */
	public function getTimerStartedAt(): DateTimeInterface|string
	{
		if (is_float($this->timerStartedAt)) {
			return Utils\DateTime::from(intval($this->timerStartedAt));
		}

		return $this->timerStartedAt;
	}

	public function getTimerDuration(): float|string
	{
		return $this->timerDuration;
	}

	public function getActivePower(): float|string
	{
		return $this->activePower;
	}

	public function getVoltage(): float|string
	{
		return $this->voltage;
	}

	public function getCurrent(): float|string
	{
		return $this->current;
	}

	public function getPowerFactor(): float|string
	{
		return $this->powerFactor;
	}

	public function getFrequency(): float|string
	{
		return $this->frequency;
	}

	public function getActiveEnergy(): ActiveEnergyStateBlock|string
	{
		return $this->activeEnergy;
	}

	public function getTemperature(): TemperatureBlockState|string
	{
		return $this->temperature;
	}

	/**
	 * {@inheritDoc}
	 *
	 * @throws Exception
	 */
	#[Override]
	public function toArray(): array
	{
		return array_merge(
			parent::toArray(),
			[
				'source' => $this->getSource(),
				'output' => $this->getOutput(),
				'timer_started_at' => $this->getTimerStartedAt() instanceof DateTimeInterface
					? $this->getTimerStartedAt()->format(DateTimeInterface::ATOM)
					: $this->getTimerStartedAt(),
				'timer_duration' => $this->getTimerDuration(),
				'active_power' => $this->getActivePower(),
				'voltage' => $this->getVoltage(),
				'current' => $this->getCurrent(),
				'power_factor' => $this->getPowerFactor(),
				'active_energy' => $this->getActiveEnergy() instanceof ActiveEnergyStateBlock ? $this->getActiveEnergy()->toArray() : null,
				'temperature' => $this->getTemperature() instanceof TemperatureBlockState ? $this->getTemperature()->toArray() : null,
			],
		);
	}

	/**
	 * {@inheritDoc}
	 */
	#[Override]
	public function toState(): array
	{
		return array_filter(
			array_merge(
				parent::toState(),
				[
					'output' => $this->getOutput(),
					'active_power' => $this->getActivePower(),
					'power_factor' => $this->getPowerFactor(),
					'current' => $this->getCurrent(),
					'voltage' => $this->getVoltage(),
				],
				$this->getActiveEnergy() instanceof ActiveEnergyStateBlock ? $this->getActiveEnergy()->toState() : [],
				$this->getTemperature() instanceof TemperatureBlockState ? $this->getTemperature()->toState() : [],
			),
			static fn ($value): bool => $value !== DeviceState::VALUE_NOT_AVAILABLE,
		);
	}

}
