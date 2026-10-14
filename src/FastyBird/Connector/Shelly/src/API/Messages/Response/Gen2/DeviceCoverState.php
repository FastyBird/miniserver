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
use function is_string;

/**
 * Generation 2 device cover state message
 */
final class DeviceCoverState extends DeviceState implements API\Messages\Message
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
			new ObjectMapper\Rules\BackedEnumValue(class: Types\Payloads\CoverPayload::class),
			new ObjectMapper\Rules\ArrayEnumValue(cases: [DeviceState::VALUE_NOT_AVAILABLE]),
		])]
		private readonly Types\Payloads\CoverPayload|string $state,
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
			new ObjectMapper\Rules\IntValue(min: 0, max: 100, unsigned: true),
			new ObjectMapper\Rules\ArrayEnumValue(cases: [DeviceState::VALUE_NOT_AVAILABLE]),
			new ObjectMapper\Rules\NullValue(),
		])]
		#[ObjectMapper\Modifiers\FieldName('current_pos')]
		private readonly int|string|null $currentPosition,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\IntValue(min: 0, max: 100, unsigned: true),
			new ObjectMapper\Rules\ArrayEnumValue(cases: [DeviceState::VALUE_NOT_AVAILABLE]),
			new ObjectMapper\Rules\NullValue(),
		])]
		#[ObjectMapper\Modifiers\FieldName('target_pos')]
		private readonly int|string|null $targetPosition,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\IntValue(),
			new ObjectMapper\Rules\ArrayEnumValue(cases: [DeviceState::VALUE_NOT_AVAILABLE]),
		])]
		#[ObjectMapper\Modifiers\FieldName('move_timeout')]
		private readonly float|string $moveTimeout,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\IntValue(),
			new ObjectMapper\Rules\ArrayEnumValue(cases: [DeviceState::VALUE_NOT_AVAILABLE]),
		])]
		#[ObjectMapper\Modifiers\FieldName('move_started_at')]
		private readonly float|string $moveStartedAt,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\BoolValue(),
			new ObjectMapper\Rules\ArrayEnumValue(cases: [DeviceState::VALUE_NOT_AVAILABLE]),
		])]
		#[ObjectMapper\Modifiers\FieldName('pos_control')]
		private readonly bool|string $hasPositionControl,
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
		return Types\ComponentType::COVER;
	}

	public function getSource(): string|null
	{
		return $this->source;
	}

	public function getState(): Types\Payloads\CoverPayload|string
	{
		return $this->state;
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

	public function getCurrentPosition(): int|string|null
	{
		return $this->currentPosition;
	}

	public function getTargetPosition(): int|string|null
	{
		return $this->targetPosition;
	}

	public function getMoveTimeout(): float|string
	{
		return $this->moveTimeout;
	}

	/**
	 * @throws Exception
	 */
	public function getMoveStartedAt(): DateTimeInterface|string
	{
		if (is_float($this->moveStartedAt)) {
			return Utils\DateTime::from(intval($this->moveStartedAt));
		}

		return $this->moveStartedAt;
	}

	public function hasPositionControl(): bool|string
	{
		return $this->hasPositionControl;
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
				'state' => is_string($this->getState()) ? $this->getState() : $this->getState()->value,
				'active_power' => $this->getActivePower(),
				'voltage' => $this->getVoltage(),
				'current' => $this->getCurrent(),
				'power_factor' => $this->getPowerFactor(),
				'current_position' => $this->getCurrentPosition(),
				'target_position' => $this->getTargetPosition(),
				'move_timeout' => $this->getMoveTimeout(),
				'move_started_at' => !$this->getMoveStartedAt() instanceof DateTimeInterface
					? $this->getMoveStartedAt()
					: $this->getMoveStartedAt()->format(DateTimeInterface::ATOM),
				'has_position_control' => $this->hasPositionControl(),
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
				parent::toArray(),
				[
					'state' => is_string($this->getState()) ? $this->getState() : $this->getState()->value,
					'current_position' => $this->getCurrentPosition(),
					'target_position' => $this->getTargetPosition(),
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
