<?php declare(strict_types = 1);

namespace FastyBird\Connector\Shelly\API\Messages\Response\Gen2;

use FastyBird\Connector\Shelly\API;
use Orisai\ObjectMapper;
use Override;

/**
 * Generation 2 device switch or cover component temperature state message
 */
final readonly class ActiveEnergyStateBlock implements API\Messages\Message
{

	/**
	 * @param array<float> $byMinute
	 */
	public function __construct(
		#[ObjectMapper\Rules\FloatValue()]
		private float $total,
		#[ObjectMapper\Rules\IntValue()]
		#[ObjectMapper\Modifiers\FieldName('minute_ts')]
		private int $minuteTs,
		#[ObjectMapper\Rules\ArrayOf(
			new ObjectMapper\Rules\FloatValue(),
			new ObjectMapper\Rules\IntValue(unsigned: true),
		)]
		#[ObjectMapper\Modifiers\FieldName('by_minute')]
		private array $byMinute = [],
	)
	{
	}

	public function getTotal(): float
	{
		return $this->total;
	}

	/**
	 * @return array<float>
	 */
	public function getByMinute(): array
	{
		return $this->byMinute;
	}

	public function getMinuteTs(): int
	{
		return $this->minuteTs;
	}

	/**
	 * {@inheritDoc}
	 */
	#[Override]
	public function toArray(): array
	{
		return [
			'total' => $this->getTotal(),
			'by_minute' => $this->getByMinute(),
			'minute_ts' => $this->getMinuteTs(),
		];
	}

	/**
	 * @return array<string, float>
	 */
	public function toState(): array
	{
		return [
			'active_energy_total' => $this->getTotal(),
		];
	}

}
