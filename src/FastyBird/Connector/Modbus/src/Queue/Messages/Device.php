<?php declare(strict_types = 1);

namespace FastyBird\Connector\Modbus\Queue\Messages;

use FastyBird\Core\Persistence\Rules;
use Override;
use Ramsey\Uuid;

/**
 * Base device message
 */
abstract class Device implements Message
{

	public function __construct(
		#[Rules\UuidValue()]
		private readonly Uuid\UuidInterface $connector,
		#[Rules\UuidValue()]
		private readonly Uuid\UuidInterface $device,
	)
	{
	}

	public function getConnector(): Uuid\UuidInterface
	{
		return $this->connector;
	}

	public function getDevice(): Uuid\UuidInterface
	{
		return $this->device;
	}

	/**
	 * {@inheritDoc}
	 */
	#[Override]
	public function toArray(): array
	{
		return [
			'connector' => $this->getConnector()->toString(),
			'device' => $this->getDevice()->toString(),
		];
	}

}
