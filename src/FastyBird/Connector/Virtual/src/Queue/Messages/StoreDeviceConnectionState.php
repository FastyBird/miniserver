<?php declare(strict_types = 1);

namespace FastyBird\Connector\Virtual\Queue\Messages;

use FastyBird\Core\Persistence\Rules;
use FastyBird\Core\Values\Types\Sources;
use FastyBird\Module\Devices\Types as DevicesTypes;
use Orisai\ObjectMapper;
use Ramsey\Uuid;

/**
 * Device state message
 */
final readonly class StoreDeviceConnectionState implements Message
{

	public function __construct(
		#[Rules\UuidValue()]
		private Uuid\UuidInterface $connector,
		#[Rules\UuidValue()]
		private Uuid\UuidInterface $device,
		#[ObjectMapper\Rules\InstanceOfValue(type: DevicesTypes\ConnectionState::class)]
		private DevicesTypes\ConnectionState $state,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\InstanceOfValue(type: Sources\Connector::class),
			new ObjectMapper\Rules\InstanceOfValue(type: Sources\Addon::class),
		])]
		private Sources\Source $source,
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

	public function getState(): DevicesTypes\ConnectionState
	{
		return $this->state;
	}

	public function getSource(): Sources\Source
	{
		return $this->source;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'connector' => $this->getConnector()->toString(),
			'device' => $this->getDevice()->toString(),
			'state' => $this->getState()->value,
			'source' => $this->getSource()->value,
		];
	}

}
