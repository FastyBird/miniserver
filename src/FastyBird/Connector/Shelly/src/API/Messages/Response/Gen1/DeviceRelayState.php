<?php declare(strict_types = 1);

namespace FastyBird\Connector\Shelly\API\Messages\Response\Gen1;

use FastyBird\Connector\Shelly\API;
use Orisai\ObjectMapper;

/**
 * Generation 1 device relay state message
 */
final readonly class DeviceRelayState implements API\Messages\Message
{

	public function __construct(
		#[ObjectMapper\Rules\BoolValue(castBoolLike: true)]
		#[ObjectMapper\Modifiers\FieldName('ison')]
		private bool $state,
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
		#[ObjectMapper\Rules\BoolValue(castBoolLike: true)]
		private bool $overpower,
		#[ObjectMapper\Rules\BoolValue(castBoolLike: true)]
		private bool $overtemperature,
		#[ObjectMapper\Rules\BoolValue(castBoolLike: true)]
		#[ObjectMapper\Modifiers\FieldName('is_valid')]
		private bool $valid,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		private string|null $source,
	)
	{
	}

	public function getState(): bool
	{
		return $this->state;
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

	public function hasOverpower(): bool
	{
		return $this->overpower;
	}

	public function hasOvertemperature(): bool
	{
		return $this->overtemperature;
	}

	public function isValid(): bool
	{
		return $this->valid;
	}

	public function getSource(): string|null
	{
		return $this->source;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'state' => $this->getState(),
			'has_timer' => $this->hasTimer(),
			'timer_started' => $this->getTimerStarted(),
			'timer_duration' => $this->getTimerDuration(),
			'timer_remaining' => $this->getTimerRemaining(),
			'overpower' => $this->hasOverpower(),
			'overtemperature' => $this->hasOvertemperature(),
			'valid' => $this->isValid(),
			'source' => $this->getSource(),
		];
	}

}
