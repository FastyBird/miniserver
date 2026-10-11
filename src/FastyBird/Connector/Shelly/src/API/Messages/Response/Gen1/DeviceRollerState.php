<?php declare(strict_types = 1);

namespace FastyBird\Connector\Shelly\API\Messages\Response\Gen1;

use FastyBird\Connector\Shelly\API;
use Orisai\ObjectMapper;

/**
 * Generation 1 device roller state message
 */
final readonly class DeviceRollerState implements API\Messages\Message
{

	public function __construct(
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private string $state,
		#[ObjectMapper\Rules\FloatValue()]
		private float $power,
		#[ObjectMapper\Rules\BoolValue(castBoolLike: true)]
		#[ObjectMapper\Modifiers\FieldName('is_valid')]
		private bool $valid,
		#[ObjectMapper\Rules\BoolValue(castBoolLike: true)]
		#[ObjectMapper\Modifiers\FieldName('safety_switch')]
		private bool $safetySwitch,
		#[ObjectMapper\Rules\BoolValue(castBoolLike: true)]
		private bool $overtemperature,
		#[ObjectMapper\Rules\ArrayEnumValue(cases: ['normal', 'safety_switch', 'obstacle', 'overpower'])]
		#[ObjectMapper\Modifiers\FieldName('stop_reason')]
		private string $stopReason,
		#[ObjectMapper\Rules\ArrayEnumValue(cases: ['open', 'close'])]
		#[ObjectMapper\Modifiers\FieldName('last_direction')]
		private string $lastDirection,
		#[ObjectMapper\Rules\IntValue(min: 0, max: 100, unsigned: true)]
		#[ObjectMapper\Modifiers\FieldName('current_pos')]
		private int $currentPos,
		#[ObjectMapper\Rules\BoolValue(castBoolLike: true)]
		private bool $calibrating,
		#[ObjectMapper\Rules\BoolValue(castBoolLike: true)]
		private bool $positioning,
	)
	{
	}

	public function getState(): string
	{
		return $this->state;
	}

	public function getPower(): float
	{
		return $this->power;
	}

	public function isValid(): bool
	{
		return $this->valid;
	}

	public function hasSafetySwitch(): bool
	{
		return $this->safetySwitch;
	}

	public function hasOvertemperature(): bool
	{
		return $this->overtemperature;
	}

	public function getStopReason(): string
	{
		return $this->stopReason;
	}

	public function getLastDirection(): string
	{
		return $this->lastDirection;
	}

	public function getCurrentPosition(): int
	{
		return $this->currentPos;
	}

	public function isCalibrating(): bool
	{
		return $this->calibrating;
	}

	public function isPositioning(): bool
	{
		return $this->positioning;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'state' => $this->getState(),
			'power' => $this->getPower(),
			'valid' => $this->isValid(),
			'safety_switch' => $this->hasSafetySwitch(),
			'overtemperature' => $this->hasOvertemperature(),
			'stop_reason' => $this->getStopReason(),
			'last_direction' => $this->getLastDirection(),
			'current_pos' => $this->getCurrentPosition(),
			'calibrating' => $this->isCalibrating(),
			'positioning' => $this->isPositioning(),
		];
	}

}
