<?php declare(strict_types = 1);

namespace FastyBird\Connector\Shelly\API\Messages\Response\Gen2;

use FastyBird\Connector\Shelly\API;
use Orisai\ObjectMapper;
use Override;

/**
 * Generation 2 device cover component motor configuration message
 */
final readonly class CoverMotorConfigurationBlock implements API\Messages\Message
{

	public function __construct(
		#[ObjectMapper\Rules\FloatValue()]
		#[ObjectMapper\Modifiers\FieldName('idle_power_thr')]
		private float $idlePowerThreshold,
		#[ObjectMapper\Rules\FloatValue()]
		#[ObjectMapper\Modifiers\FieldName('idle_confirm_period')]
		private float $idleConfirmPeriod,
	)
	{
	}

	public function getIdlePowerThreshold(): float
	{
		return $this->idlePowerThreshold;
	}

	public function getIdleConfirmPeriod(): float
	{
		return $this->idleConfirmPeriod;
	}

	/**
	 * {@inheritDoc}
	 */
	#[Override]
	public function toArray(): array
	{
		return [
			'idle_power_threshold' => $this->getIdlePowerThreshold(),
			'idle_confirm_period' => $this->getIdleConfirmPeriod(),
		];
	}

}
