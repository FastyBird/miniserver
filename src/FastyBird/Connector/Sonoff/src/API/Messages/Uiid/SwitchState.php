<?php declare(strict_types = 1);

namespace FastyBird\Connector\Sonoff\API\Messages\Uiid;

use Orisai\ObjectMapper;

/**
 * Switch state entity
 */
final readonly class SwitchState implements ObjectMapper\MappedObject
{

	private const string VALUE_ON = 'on';

	private const string VALUE_OFF = 'off';

	public function __construct(
		#[ObjectMapper\Rules\ArrayEnumValue(cases: [self::VALUE_ON, self::VALUE_OFF])]
		private string $switch,
		#[ObjectMapper\Rules\IntValue(min: 0, max: 10, unsigned: true, castNumericString: true)]
		private int $outlet,
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
	 * @return array<string, int|string>
	 */
	public function toArray(): array
	{
		return [
			'switch' => $this->getSwitch(),
			'outlet' => $this->getOutlet(),
		];
	}

}
