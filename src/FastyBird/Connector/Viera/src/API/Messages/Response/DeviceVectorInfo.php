<?php declare(strict_types = 1);

namespace FastyBird\Connector\Viera\API\Messages\Response;

use FastyBird\Connector\Viera\API;
use Orisai\ObjectMapper;
use Override;

/**
 * Device vector info message
 */
final readonly class DeviceVectorInfo implements API\Messages\Message
{

	public function __construct(
		#[ObjectMapper\Rules\IntValue(unsigned: true)]
		private int $port,
	)
	{
	}

	public function getPort(): int
	{
		return $this->port;
	}

	/**
	 * {@inheritDoc}
	 */
	#[Override]
	public function toArray(): array
	{
		return [
			'port' => $this->getPort(),
		];
	}

}
