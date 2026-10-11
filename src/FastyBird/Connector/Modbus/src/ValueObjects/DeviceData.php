<?php declare(strict_types = 1);

namespace FastyBird\Connector\Modbus\ValueObjects;

use FastyBird\Core\Values\Types;
use Nette;

/**
 * Data to be sent to device
 */
class DeviceData
{

	use Nette\SmartObject;

	private Types\DataType $dataType;

	public function __construct(
		private readonly string|int|float|bool|null $value,
		Types\DataType|null $dataType,
	)
	{
		$this->dataType = $dataType ?? Types\DataType::STRING;
	}

	public function getValue(): float|bool|int|string|null
	{
		return $this->value;
	}

	public function getDataType(): Types\DataType
	{
		return $this->dataType;
	}

}
