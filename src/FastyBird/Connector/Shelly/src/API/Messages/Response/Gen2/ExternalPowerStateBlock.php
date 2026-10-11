<?php declare(strict_types = 1);

namespace FastyBird\Connector\Shelly\API\Messages\Response\Gen2;

use FastyBird\Connector\Shelly\API;
use Orisai\ObjectMapper;
use Override;

/**
 * Generation 2 device external power state message
 */
final readonly class ExternalPowerStateBlock implements API\Messages\Message
{

	public function __construct(
		#[ObjectMapper\Rules\BoolValue()]
		private bool $present,
	)
	{
	}

	public function isPresent(): bool
	{
		return $this->present;
	}

	/**
	 * {@inheritDoc}
	 */
	#[Override]
	public function toArray(): array
	{
		return [
			'present' => $this->isPresent(),
		];
	}

	/**
	 * @return array<string, bool>
	 */
	public function toState(): array
	{
		return [
			'external_present' => $this->isPresent(),
		];
	}

}
