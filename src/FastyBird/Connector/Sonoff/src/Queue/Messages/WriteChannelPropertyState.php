<?php declare(strict_types = 1);

namespace FastyBird\Connector\Sonoff\Queue\Messages;

use FastyBird\Core\Persistence\Rules;
use Orisai\ObjectMapper;
use Override;
use Ramsey\Uuid;

/**
 * Write updated channel property state to hardware message entity
 */
final readonly class WriteChannelPropertyState implements Message
{

	public function __construct(
		#[Rules\UuidValue()]
		private Uuid\UuidInterface $connector,
		#[Rules\UuidValue()]
		private Uuid\UuidInterface $device,
		#[Rules\UuidValue()]
		private Uuid\UuidInterface $channel,
		#[Rules\UuidValue()]
		private Uuid\UuidInterface $property,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\MappedObjectValue(class: State::class),
			new ObjectMapper\Rules\NullValue(),
		])]
		private State|null $state = null,
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
