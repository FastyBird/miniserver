<?php declare(strict_types = 1);

namespace FastyBird\Connector\Modbus\API\Messages\Response;

use FastyBird\Connector\Modbus\API;
use FastyBird\Connector\Modbus\Types;
use Override;

/**
 * Write single coil register response
 */
final readonly class WriteCoil implements API\Messages\Message
{

	public function __construct(
		private int $station,
		private Types\ModbusFunction $function,
		private bool $value,
	)
	{
	}

	public function getStation(): int
	{
		return $this->station;
	}

	public function getFunction(): Types\ModbusFunction
	{
		return $this->function;
	}

	public function getValue(): bool
	{
		return $this->value;
	}

	#[Override]
	public function toArray(): array
	{
		return [
			'station' => $this->getStation(),
			'function' => $this->getFunction()->value,
			'value' => $this->getValue(),
		];
	}

}
