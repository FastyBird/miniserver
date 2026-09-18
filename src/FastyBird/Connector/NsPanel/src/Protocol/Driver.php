<?php declare(strict_types = 1);

/**
 * Driver.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Protocol
 * @since          1.0.0
 *
 * @date           04.10.24
 */

namespace FastyBird\Connector\NsPanel\Protocol;

use FastyBird\Connector\NsPanel\Exceptions;
use FastyBird\Connector\NsPanel\Protocol;
use Nette;
use Ramsey\Uuid;
use SplObjectStorage;
use function is_string;

/**
 * NS panel device driver service
 *
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Protocol
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class Driver
{

	use Nette\SmartObject;

	/** @var SplObjectStorage<Protocol\Devices\Device, null> */
	private SplObjectStorage $devices;

	public function __construct()
	{
		$this->devices = new SplObjectStorage();
	}

	public function reset(): void
	{
		$this->devices = new SplObjectStorage();
	}

	/**
	 * @throws Exceptions\InvalidArgument
	 */
	public function addDevice(Devices\Device $device): void
	{
		$this->devices->rewind();

		foreach ($this->devices as $existingDevice) {
			if ($existingDevice->getId() === $device->getId()) {
				throw new Exceptions\InvalidArgument('Duplicate ID found when attempting to add device');
			}
		}

		$this->devices->offsetSet($device);
	}

	/**
	 * @return array<Protocol\Devices\Device>
	 */
	public function getDevices(): array
	{
		$this->devices->rewind();

		$devices = [];

		foreach ($this->devices as $device) {
			$devices[] = $device;
		}

		return $devices;
	}

	/**
	 * @return array<Protocol\Devices\Device>
	 */
	public function findDevices(Uuid\UuidInterface $gatewayId): array
	{
		$devices = [];

		$this->devices->rewind();

		foreach ($this->devices as $device) {
			if ($device->getParent()->equals($gatewayId)) {
				$devices[] = $device;
			}
		}

		return $devices;
	}

	public function findDevice(Uuid\UuidInterface|string $idOrIdentifier): Devices\Device|null
	{
		$this->devices->rewind();

		foreach ($this->devices as $device) {
			if (
				($idOrIdentifier instanceof Uuid\UuidInterface && $device->getId()->equals($idOrIdentifier))
				|| (is_string($idOrIdentifier) && $device->getIdentifier() === $idOrIdentifier)
			) {
				return $device;
			}
		}

		return null;
	}

	/**
	 * @return array<int, array<string, mixed>>
	 *
	 * @throws Exceptions\InvalidState
	 */
	public function toRepresentation(): array
	{
		$representation = [];

		foreach ($this->getDevices() as $device) {
			$representation[] = $device->toRepresentation();
		}

		return $representation;
	}

}
