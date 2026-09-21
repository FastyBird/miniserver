<?php declare(strict_types = 1);

/**
 * ConnectionManager.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ShellyConnector!
 * @subpackage     API
 * @since          1.0.0
 *
 * @date           23.08.23
 */

namespace FastyBird\Connector\Shelly\API;

use FastyBird\Connector\Shelly\Documents;
use FastyBird\Connector\Shelly\Entities;
use FastyBird\Connector\Shelly\Exceptions;
use FastyBird\Connector\Shelly\Helpers;
use FastyBird\Core\Exceptions as ApplicationExceptions;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use Nette;
use TypeError;
use ValueError;
use function array_key_exists;

/**
 * API connections manager
 *
 * @package        FastyBird:ShellyConnector!
 * @subpackage     API
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class ConnectionManager
{

	use Nette\SmartObject;

	private Gen1HttpApi|null $gen1ApiConnection = null;

	private Gen1Coap|null $gen1CoapApiConnection = null;

	private Gen2HttpApi|null $gen2ApiConnection = null;

	/** @var array<string, Gen2WsApi> */
	private array $gen2WsApiConnection = [];

	public function __construct(
		private readonly Gen1HttpApiFactory $gen1HttpApiFactory,
		private readonly Gen1CoapFactory $gen1CoapFactory,
		private readonly Gen2HttpApiFactory $gen2HttpApiFactory,
		private readonly Gen2WsApiFactory $wsApiFactory,
		private readonly Helpers\Device $deviceHelper,
	)
	{
	}

	public function getGen1HttpApiConnection(): Gen1HttpApi
	{
		if ($this->gen1ApiConnection === null) {
			$this->gen1ApiConnection = $this->gen1HttpApiFactory->create();
		}

		return $this->gen1ApiConnection;
	}

	public function getGen1CoapApiConnection(): Gen1Coap
	{
		if ($this->gen1CoapApiConnection === null) {
			$this->gen1CoapApiConnection = $this->gen1CoapFactory->create();
		}

		return $this->gen1CoapApiConnection;
	}

	public function getGen2HttpApiConnection(): Gen2HttpApi
	{
		if ($this->gen2ApiConnection === null) {
			$this->gen2ApiConnection = $this->gen2HttpApiFactory->create();
		}

		return $this->gen2ApiConnection;
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function getGen2WsApiConnection(
		Entities\Devices\Device|Documents\Devices\Device $device,
	): Gen2WsApi
	{
		if (array_key_exists($device->getId()->toString(), $this->gen2WsApiConnection)) {
			$this->gen2WsApiConnection[$device->getId()->toString()]->disconnect();
		}

		$this->gen2WsApiConnection[$device->getId()->toString()] = $this->wsApiFactory->create(
			$device->getId(),
			$this->getIpAddress($device),
			$this->getDomain($device),
			$this->getUsername($device),
			$this->getPassword($device),
		);

		return $this->gen2WsApiConnection[$device->getId()->toString()];
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	private function getIpAddress(
		Entities\Devices\Device|Documents\Devices\Device $device,
	): string|null
	{
		if ($device instanceof Entities\Devices\Device) {
			return $device->getIpAddress();
		}

		return $this->deviceHelper->getIpAddress($device);
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	private function getDomain(
		Entities\Devices\Device|Documents\Devices\Device $device,
	): string|null
	{
		if ($device instanceof Entities\Devices\Device) {
			return $device->getDomain();
		}

		return $this->deviceHelper->getDomain($device);
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	private function getUsername(
		Entities\Devices\Device|Documents\Devices\Device $device,
	): string|null
	{
		if ($device instanceof Entities\Devices\Device) {
			return $device->getUsername();
		}

		return $this->deviceHelper->getUsername($device);
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	private function getPassword(
		Entities\Devices\Device|Documents\Devices\Device $device,
	): string|null
	{
		if ($device instanceof Entities\Devices\Device) {
			return $device->getPassword();
		}

		return $this->deviceHelper->getPassword($device);
	}

	public function __destruct()
	{
		foreach ($this->gen2WsApiConnection as $key => $connection) {
			$connection->disconnect();

			unset($this->gen2WsApiConnection[$key]);
		}
	}

}
