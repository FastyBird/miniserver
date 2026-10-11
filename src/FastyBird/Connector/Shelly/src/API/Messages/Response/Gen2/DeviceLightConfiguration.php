<?php declare(strict_types = 1);

namespace FastyBird\Connector\Shelly\API\Messages\Response\Gen2;

use FastyBird\Connector\Shelly\API;
use FastyBird\Connector\Shelly\Types;
use Orisai\ObjectMapper;

/**
 * Generation 2 device light configuration message
 */
final readonly class DeviceLightConfiguration implements API\Messages\Message
{

	public function __construct(
		#[ObjectMapper\Rules\IntValue(unsigned: true)]
		private int $id,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		private string|null $name,
		#[ObjectMapper\Rules\ArrayEnumValue(cases: ['off', 'on', 'restore_last', 'match_input'])]
		#[ObjectMapper\Modifiers\FieldName('initial_state')]
		private string $initialState,
		#[ObjectMapper\Rules\BoolValue()]
		#[ObjectMapper\Modifiers\FieldName('auto_on')]
		private bool $autoOn,
		#[ObjectMapper\Rules\IntValue()]
		#[ObjectMapper\Modifiers\FieldName('auto_on_delay')]
		private int $autoOnDelay,
		#[ObjectMapper\Rules\BoolValue()]
		#[ObjectMapper\Modifiers\FieldName('auto_off')]
		private bool $autoOff,
		#[ObjectMapper\Rules\IntValue()]
		#[ObjectMapper\Modifiers\FieldName('auto_off_delay')]
		private int $autoOffDelay,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\MappedObjectValue(class: LightDefaultConfigurationBlock::class),
			new ObjectMapper\Rules\NullValue(),
		])]
		private LightDefaultConfigurationBlock|null $default,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\MappedObjectValue(class: LightNightModeConfigurationBlock::class),
			new ObjectMapper\Rules\NullValue(),
		])]
		#[ObjectMapper\Modifiers\FieldName('night_mode')]
		private LightNightModeConfigurationBlock|null $nightMode,
	)
	{
	}

	public function getId(): int
	{
		return $this->id;
	}

	public function getType(): Types\ComponentType
	{
		return Types\ComponentType::LIGHT;
	}

	public function getName(): string|null
	{
		return $this->name;
	}

	public function getInitialState(): string
	{
		return $this->initialState;
	}

	public function hasAutoOn(): bool
	{
		return $this->autoOn;
	}

	public function getAutoOnDelay(): int
	{
		return $this->autoOnDelay;
	}

	public function hasAutoOff(): bool
	{
		return $this->autoOff;
	}

	public function getAutoOffDelay(): int
	{
		return $this->autoOffDelay;
	}

	public function getDefault(): LightDefaultConfigurationBlock|null
	{
		return $this->default;
	}

	public function getNightMode(): LightNightModeConfigurationBlock|null
	{
		return $this->nightMode;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'id' => $this->getId(),
			'type' => $this->getType()->value,
			'name' => $this->getName(),
			'initial_state' => $this->getInitialState(),
			'auto_on' => $this->hasAutoOn(),
			'auto_on_delay' => $this->getAutoOnDelay(),
			'auto_off' => $this->hasAutoOff(),
			'auto_off_delay' => $this->getAutoOffDelay(),
			'default' => $this->getDefault()?->toArray(),
			'night_mode' => $this->getNightMode()?->toArray(),
		];
	}

}
