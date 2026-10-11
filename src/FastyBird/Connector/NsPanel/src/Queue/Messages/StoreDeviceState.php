<?php declare(strict_types = 1);

namespace FastyBird\Connector\NsPanel\Queue\Messages;

use FastyBird\Core\Persistence\Rules;
use Orisai\ObjectMapper;
use Ramsey\Uuid;
use function array_map;

/**
 * Store device state message
 */
final readonly class StoreDeviceState implements Message
{

	/**
	 * @param array<CapabilityState> $state
	 */
	public function __construct(
		#[Rules\UuidValue()]
		private Uuid\UuidInterface $connector,
		#[Rules\UuidValue()]
		private Uuid\UuidInterface $gateway,
		#[Rules\UuidValue()]
		private Uuid\UuidInterface $device,
		#[ObjectMapper\Rules\ArrayOf(
			new ObjectMapper\Rules\MappedObjectValue(CapabilityState::class),
		)]
		private array $state,
	)
	{
	}

	public function getConnector(): Uuid\UuidInterface
	{
		return $this->connector;
	}

	public function getGateway(): Uuid\UuidInterface
	{
		return $this->gateway;
	}

	public function getDevice(): Uuid\UuidInterface
	{
		return $this->device;
	}

	/**
	 * @return array<CapabilityState>
	 */
	public function getState(): array
	{
		return $this->state;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'connector' => $this->getConnector()->toString(),
			'gateway' => $this->getGateway()->toString(),
			'device' => $this->getDevice()->toString(),
			'state' => array_map(
				static fn (CapabilityState $state): array => $state->toArray(),
				$this->getState(),
			),
		];
	}

}
