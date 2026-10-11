<?php declare(strict_types = 1);

namespace FastyBird\Connector\Modbus\API;

use FastyBird\Connector\Modbus\Documents;
use FastyBird\Connector\Modbus\Exceptions as ModbusExceptions;
use FastyBird\Connector\Modbus\Helpers;
use FastyBird\Core\Exceptions as CoreExceptions;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use TypeError;
use ValueError;

/**
 * Client connections manager
 */
final class ConnectionManager
{

	private Rtu|null $rtuClient = null;

	private Tcp|null $tcpClient = null;

	public function __construct(
		private readonly RtuFactory $rtuFactory,
		private readonly TcpFactory $tcpFactory,
		private readonly Helpers\Connector $connectorHelper,
	)
	{
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws ModbusExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function getRtuClient(Documents\Connectors\Connector $connector): Rtu
	{
		if ($this->rtuClient === null) {
			$this->rtuClient = $this->rtuFactory->create(
				$this->connectorHelper->getBaudRate($connector),
				$this->connectorHelper->getByteSize($connector),
				$this->connectorHelper->getStopBits($connector),
				$this->connectorHelper->getParity($connector),
				$this->connectorHelper->getRtuInterface($connector),
			);
		}

		return $this->rtuClient;
	}

	public function getTcpClient(): Tcp
	{
		if ($this->tcpClient === null) {
			$this->tcpClient = $this->tcpFactory->create();
		}

		return $this->tcpClient;
	}

	/**
	 * @throws ModbusExceptions\InvalidState
	 */
	public function __destruct()
	{
		$this->rtuClient?->close();
	}

}
