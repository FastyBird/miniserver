<?php declare(strict_types = 1);

namespace FastyBird\Connector\Shelly\API\Messages\Response\Gen2;

use FastyBird\Connector\Shelly\API;
use Orisai\ObjectMapper;
use Override;

/**
 * Generation 2 device light component default configuration message
 */
final readonly class LightDefaultConfigurationBlock implements API\Messages\Message
{

	public function __construct(
		#[ObjectMapper\Rules\IntValue(min: 0, max: 100, unsigned: true)]
		private int $brightness,
	)
	{
	}

	public function getBrightness(): int
	{
		return $this->brightness;
	}

	/**
	 * {@inheritDoc}
	 */
	#[Override]
	public function toArray(): array
	{
		return [
			'brightness' => $this->getBrightness(),
		];
	}

}
