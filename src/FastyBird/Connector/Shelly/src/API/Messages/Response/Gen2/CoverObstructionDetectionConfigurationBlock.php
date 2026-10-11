<?php declare(strict_types = 1);

namespace FastyBird\Connector\Shelly\API\Messages\Response\Gen2;

use FastyBird\Connector\Shelly\API;
use Orisai\ObjectMapper;
use Override;

/**
 * Generation 2 device cover component obstruction detection configuration message
 */
final readonly class CoverObstructionDetectionConfigurationBlock implements API\Messages\Message
{

	public function __construct(
		#[ObjectMapper\Rules\BoolValue()]
		private bool $enable,
		#[ObjectMapper\Rules\ArrayEnumValue(cases: ['open', 'closed', 'both'])]
		private string $direction,
		#[ObjectMapper\Rules\ArrayEnumValue(cases: ['stop', 'reverse'])]
		private string $action,
		#[ObjectMapper\Rules\FloatValue()]
		#[ObjectMapper\Modifiers\FieldName('power_thr')]
		private float $powerThreshold,
		#[ObjectMapper\Rules\FloatValue()]
		private float $holdoff,
	)
	{
	}

	public function isEnabled(): bool
	{
		return $this->enable;
	}

	public function getDirection(): string
	{
		return $this->direction;
	}

	public function getAction(): string
	{
		return $this->action;
	}

	public function getPowerThreshold(): float
	{
		return $this->powerThreshold;
	}

	public function getHoldoff(): float
	{
		return $this->holdoff;
	}

	/**
	 * {@inheritDoc}
	 */
	#[Override]
	public function toArray(): array
	{
		return [
			'enabled' => $this->isEnabled(),
			'direction' => $this->getDirection(),
			'action' => $this->getAction(),
			'power_threshold' => $this->getPowerThreshold(),
			'holdoff' => $this->getHoldoff(),
		];
	}

}
