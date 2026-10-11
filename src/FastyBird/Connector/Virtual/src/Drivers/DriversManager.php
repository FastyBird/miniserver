<?php declare(strict_types = 1);

namespace FastyBird\Connector\Virtual\Drivers;

use FastyBird\Connector\Virtual\Documents;
use FastyBird\Connector\Virtual\Exceptions;
use Throwable;
use function array_key_exists;
use function sprintf;

/**
 * API connections manager
 */
final class DriversManager
{

	/** @var array<string, Driver> */
	private array $drivers = [];

	/**
	 * @param array<DriverFactory> $driversFactories
	 */
	public function __construct(private readonly array $driversFactories)
	{
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	public function getDriver(Documents\Devices\Device $device): Driver
	{
		if (!array_key_exists($device->getId()->toString(), $this->drivers)) {
			foreach ($this->driversFactories as $factory) {
				if ($device::getType() === $factory::DEVICE_TYPE) {
					$driver = $factory->create($device);

					$this->drivers[$device->getId()->toString()] = $driver;
				}
			}
		}

		if (!array_key_exists($device->getId()->toString(), $this->drivers)) {
			throw new Exceptions\InvalidState(sprintf('Driver for device: %s could not be created', $device::class));
		}

		return $this->drivers[$device->getId()->toString()];
	}

	public function __destruct()
	{
		foreach ($this->drivers as $key => $driver) {
			try {
				$driver->disconnect();
			} catch (Throwable) {
				// Just ignore
			}

			unset($this->drivers[$key]);
		}
	}

}
