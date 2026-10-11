<?php declare(strict_types = 1);

namespace FastyBird\Connector\Viera\Queue\Messages;

use FastyBird\Module\Devices\Types as DevicesTypes;
use Orisai\ObjectMapper;
use Override;
use Ramsey\Uuid;
use function array_merge;

/**
 * Store device connection state message
 */
final class StoreDeviceConnectionState extends Device implements Message
{

	public function __construct(
		Uuid\UuidInterface $connector,
		Uuid\UuidInterface $device,
		#[ObjectMapper\Rules\InstanceOfValue(type: DevicesTypes\ConnectionState::class)]
		private readonly DevicesTypes\ConnectionState $state,
	)
	{
		parent::__construct($connector, $device);
	}

	public function getState(): DevicesTypes\ConnectionState
	{
		return $this->state;
	}

	/**
	 * {@inheritDoc}
	 */
	#[Override]
	public function toArray(): array
	{
		return array_merge(parent::toArray(), [
			'state' => $this->getState()->value,
		]);
	}

}
