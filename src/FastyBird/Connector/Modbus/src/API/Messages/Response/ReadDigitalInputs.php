<?php declare(strict_types = 1);

namespace FastyBird\Connector\Modbus\API\Messages\Response;

use FastyBird\Connector\Modbus\API;
use FastyBird\Connector\Modbus\Types;
use Nette;
use Override;
use function array_key_exists;

/**
 * Digital registers reading response
 */
final readonly class ReadDigitalInputs implements API\Messages\Message
{

	use Nette\SmartObject;

	/**
	 * @param array<int, bool> $registers
	 */
	public function __construct(
		private int $station,
		private Types\ModbusFunction $function,
		private int $count,
		private array $registers,
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

	public function getCount(): int
	{
		return $this->count;
	}

	/**
	 * @return array<int, bool>
	 */
	public function getRegisters(): array
	{
		return $this->registers;
	}

	public function findRegister(int $address): bool|null
	{
		if (!array_key_exists($address, $this->registers)) {
			return null;
		}

		return $this->registers[$address];
	}

	#[Override]
	public function toArray(): array
	{
		return [
			'station' => $this->getStation(),
			'function' => $this->getFunction()->value,
			'count' => $this->getCount(),
			'registers' => $this->getRegisters(),
		];
	}

}
