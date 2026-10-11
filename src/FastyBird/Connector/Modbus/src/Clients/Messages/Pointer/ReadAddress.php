<?php declare(strict_types = 1);

namespace FastyBird\Connector\Modbus\Clients\Messages\Pointer;

use FastyBird\Connector\Modbus\Clients;
use FastyBird\Connector\Modbus\Documents;
use FastyBird\Core\Values\Types;
use Override;

/**
 * Base read register address request
 */
abstract class ReadAddress implements Clients\Messages\Message
{

	public function __construct(
		private readonly int $address,
		private readonly Documents\Channels\Channel $channel,
		private readonly Types\DataType $dataType,
	)
	{
	}

	public function getAddress(): int
	{
		return $this->address;
	}

	public function getChannel(): Documents\Channels\Channel
	{
		return $this->channel;
	}

	public function getDataType(): Types\DataType
	{
		return $this->dataType;
	}

	public function getSize(): int
	{
		if (
			$this->getDataType() === Types\DataType::INT
			|| $this->getDataType() === Types\DataType::UINT
			|| $this->getDataType() === Types\DataType::FLOAT
		) {
			return 2;
		}

		return 1;
	}

	#[Override]
	public function toArray(): array
	{
		return [
			'address' => $this->getAddress(),
			'channel' => $this->getChannel()->getIdentifier(),
			'data_type' => $this->getDataType()->value,
		];
	}

}
