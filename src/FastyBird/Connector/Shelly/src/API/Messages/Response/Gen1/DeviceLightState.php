<?php declare(strict_types = 1);

namespace FastyBird\Connector\Shelly\API\Messages\Response\Gen1;

use FastyBird\Connector\Shelly\API;
use Orisai\ObjectMapper;
use Override;

/**
 * Generation 1 device light state message
 */
final readonly class DeviceLightState implements API\Messages\Message
{

	public function __construct(
		#[ObjectMapper\Rules\BoolValue(castBoolLike: true)]
		#[ObjectMapper\Modifiers\FieldName('ison')]
		private bool $state,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private string $source,
		#[ObjectMapper\Rules\BoolValue(castBoolLike: true)]
		#[ObjectMapper\Modifiers\FieldName('has_timer')]
		private bool $hasTimer,
		#[ObjectMapper\Rules\FloatValue()]
		#[ObjectMapper\Modifiers\FieldName('timer_started')]
		private float $timerStarted,
		#[ObjectMapper\Rules\FloatValue()]
		#[ObjectMapper\Modifiers\FieldName('timer_duration')]
		private float $timerDuration,
		#[ObjectMapper\Rules\FloatValue()]
		#[ObjectMapper\Modifiers\FieldName('timer_remaining')]
		private float $timerRemaining,
		#[ObjectMapper\Rules\ArrayEnumValue(cases: ['color', 'white'])]
		private string $mode,
		#[ObjectMapper\Rules\IntValue(min: 0, max: 255, unsigned: true)]
		private int $red,
		#[ObjectMapper\Rules\IntValue(min: 0, max: 255, unsigned: true)]
		private int $green,
		#[ObjectMapper\Rules\IntValue(min: 0, max: 255, unsigned: true)]
		private int $blue,
		#[ObjectMapper\Rules\IntValue(min: 0, max: 255, unsigned: true)]
		private int $white,
		#[ObjectMapper\Rules\IntValue(min: 0, max: 255, unsigned: true)]
		private int $gain,
		#[ObjectMapper\Rules\IntValue(min: 2_700, max: 6_500, unsigned: true)]
		#[ObjectMapper\Modifiers\FieldName('temp')]
		private int $temperature,
		#[ObjectMapper\Rules\IntValue(min: 0, max: 100, unsigned: true)]
		private int $brightness,
		#[ObjectMapper\Rules\IntValue()]
		private int $effect,
		#[ObjectMapper\Rules\IntValue()]
		private int $transition,
	)
	{
	}

	public function getState(): bool
	{
		return $this->state;
	}

	public function getSource(): string
	{
		return $this->source;
	}

	public function hasTimer(): bool
	{
		return $this->hasTimer;
	}

	public function getTimerStarted(): float
	{
		return $this->timerStarted;
	}

	public function getTimerDuration(): float
	{
		return $this->timerDuration;
	}

	public function getTimerRemaining(): float
	{
		return $this->timerRemaining;
	}

	public function getMode(): string
	{
		return $this->mode;
	}

	public function getRed(): int
	{
		return $this->red;
	}

	public function getGreen(): int
	{
		return $this->green;
	}

	public function getBlue(): int
	{
		return $this->blue;
	}

	public function getWhite(): int
	{
		return $this->white;
	}

	public function getGain(): int
	{
		return $this->gain;
	}

	public function getTemperature(): int
	{
		return $this->temperature;
	}

	public function getBrightness(): int
	{
		return $this->brightness;
	}

	public function getEffect(): int
	{
		return $this->effect;
	}

	public function getTransition(): int
	{
		return $this->transition;
	}

	/**
	 * {@inheritDoc}
	 */
	#[Override]
	public function toArray(): array
	{
		return [
			'state' => $this->getState(),
			'source' => $this->getSource(),
			'has_timer' => $this->hasTimer(),
			'timer_started' => $this->getTimerStarted(),
			'timer_duration' => $this->getTimerDuration(),
			'timer_remaining' => $this->getTimerRemaining(),
			'mode' => $this->getMode(),
			'red' => $this->getRed(),
			'green' => $this->getGreen(),
			'blue' => $this->getBlue(),
			'white' => $this->getWhite(),
			'gain' => $this->getGain(),
			'temperature' => $this->getTemperature(),
			'brightness' => $this->getBrightness(),
			'effect' => $this->getEffect(),
			'transition' => $this->getTransition(),
		];
	}

}
