<?php declare(strict_types = 1);

namespace FastyBird\Connector\NsPanel\Queue\Messages;

use FastyBird\Core\Persistence\Rules;
use Orisai\ObjectMapper;
use Override;
use Ramsey\Uuid;

/**
 * Write updated device state to hardware message
 */
abstract readonly class WriteDeviceState implements Message
{

	public function __construct(
		#[Rules\UuidValue()]
		private readonly Uuid\UuidInterface $connector,
		#[Rules\UuidValue()]
		private readonly Uuid\UuidInterface $device,
		#[Rules\UuidValue()]
		private readonly Uuid\UuidInterface $channel,
		#[Rules\UuidValue()]
		private readonly Uuid\UuidInterface $property,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\MappedObjectValue(class: State::class),
			new ObjectMapper\Rules\NullValue(),
		])]
		private readonly State|null $state,
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

	public function getChannel(): Uuid\UuidInterface
	{
		return $this->channel;
	}

	public function getProperty(): Uuid\UuidInterface
	{
		return $this->property;
	}

	public function getState(): State|null
	{
		return $this->state;
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
			'channel' => $this->getChannel()->toString(),
			'property' => $this->getProperty()->toString(),
			'state' => $this->getState()?->toArray(),
		];
	}

}
