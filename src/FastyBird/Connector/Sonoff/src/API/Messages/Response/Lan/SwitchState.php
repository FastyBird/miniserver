<?php declare(strict_types = 1);

namespace FastyBird\Connector\Sonoff\API\Messages\Response\Lan;

use FastyBird\Connector\Sonoff\API;
use Orisai\ObjectMapper;
use Override;

/**
 * Switch state entity
 */
final class SwitchState implements API\Messages\Message
{

	private const string VALUE_ON = 'on';

	private const string VALUE_OFF = 'off';

	public function __construct(
		#[ObjectMapper\Rules\ArrayEnumValue(cases: [self::VALUE_ON, self::VALUE_OFF])]
		private readonly string $switch,
		#[ObjectMapper\Rules\IntValue(min: 0, max: 10, unsigned: true)]
		private readonly int $outlet,
	)
	{
	}

	public function getSwitch(): string
	{
		return $this->switch;
	}

	public function getOutlet(): int
	{
		return $this->outlet;
	}

	/**
	 * {@inheritDoc}
	 */
	#[Override]
	public function toArray(): array
	{
		return [
			'switch' => $this->getSwitch(),
			'outlet' => $this->getOutlet(),
		];
	}

}
