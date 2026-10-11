<?php declare(strict_types = 1);

namespace FastyBird\Connector\Sonoff\API\Messages\Uiid;

use Orisai\ObjectMapper;

/**
 * Switch configuration entity
 */
final class SwitchConfiguration implements ObjectMapper\MappedObject
{

	private const string VALUE_ON = 'on';

	private const string VALUE_OFF = 'off';

	private const string VALUE_STAY = 'stay';

	public function __construct(
		#[ObjectMapper\Rules\ArrayEnumValue(cases: [self::VALUE_ON, self::VALUE_OFF, self::VALUE_STAY])]
		private readonly string $startup,
		#[ObjectMapper\Rules\IntValue(min: 0, max: 10, unsigned: true, castNumericString: true)]
		private readonly int $outlet,
	)
	{
	}

	public function getStartup(): string
	{
		return $this->startup;
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
			'startup' => $this->getStartup(),
			'outlet' => $this->getOutlet(),
		];
	}

}
