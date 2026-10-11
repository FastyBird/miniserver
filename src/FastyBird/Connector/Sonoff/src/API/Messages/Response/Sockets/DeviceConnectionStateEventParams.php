<?php declare(strict_types = 1);

namespace FastyBird\Connector\Sonoff\API\Messages\Response\Sockets;

use FastyBird\Connector\Sonoff\API;
use Orisai\ObjectMapper;
use Override;

/**
 * Device reported state params entity
 */
final readonly class DeviceConnectionStateEventParams implements API\Messages\Message
{

	public function __construct(
		#[ObjectMapper\Rules\BoolValue(castBoolLike: true)]
		private bool $online,
	)
	{
	}

	public function isOnline(): bool
	{
		return $this->online;
	}

	/**
	 * {@inheritDoc}
	 */
	#[Override]
	public function toArray(): array
	{
		return [
			'is_online' => $this->isOnline(),
		];
	}

}
