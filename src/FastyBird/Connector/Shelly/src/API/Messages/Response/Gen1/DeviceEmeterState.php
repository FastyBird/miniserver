<?php declare(strict_types = 1);

namespace FastyBird\Connector\Shelly\API\Messages\Response\Gen1;

use FastyBird\Connector\Shelly\API;
use Orisai\ObjectMapper;

/**
 * Generation 1 device energy meter state message
 */
final readonly class DeviceEmeterState implements API\Messages\Message
{

	public function __construct(
		#[ObjectMapper\Rules\FloatValue()]
		#[ObjectMapper\Modifiers\FieldName('power')]
		private float $activePower,
		#[ObjectMapper\Rules\FloatValue()]
		#[ObjectMapper\Modifiers\FieldName('pf')]
		private float $powerFactor,
		#[ObjectMapper\Rules\FloatValue()]
		#[ObjectMapper\Modifiers\FieldName('reactive')]
		private float $reactivePower,
		#[ObjectMapper\Rules\FloatValue()]
		private float $current,
		#[ObjectMapper\Rules\FloatValue()]
		private float $voltage,
		#[ObjectMapper\Rules\BoolValue(castBoolLike: true)]
		#[ObjectMapper\Modifiers\FieldName('is_valid')]
		private bool $valid,
		#[ObjectMapper\Rules\FloatValue()]
		private float $total,
		#[ObjectMapper\Rules\FloatValue()]
		#[ObjectMapper\Modifiers\FieldName('total_returned')]
		private float $totalReturned,
	)
	{
	}

	public function getActivePower(): float
	{
		return $this->activePower;
	}

	public function getPowerFactor(): float
	{
		return $this->powerFactor;
	}

	public function getReactivePower(): float
	{
		return $this->reactivePower;
	}

	public function getCurrent(): float
	{
		return $this->current;
	}

	public function getVoltage(): float
	{
		return $this->voltage;
	}

	public function isValid(): bool
	{
		return $this->valid;
	}

	public function getTotal(): float
	{
		return $this->total;
	}

	public function getTotalReturned(): float
	{
		return $this->totalReturned;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'active_power' => $this->getActivePower(),
			'power_factor' => $this->getPowerFactor(),
			'reactive_power' => $this->getReactivePower(),
			'current' => $this->getCurrent(),
			'voltage' => $this->getVoltage(),
			'valid' => $this->isValid(),
			'total' => $this->getTotal(),
			'total_returned' => $this->getTotalReturned(),
		];
	}

}
