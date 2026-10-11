<?php declare(strict_types = 1);

namespace FastyBird\Connector\Modbus\Clients\Messages\Request;

use FastyBird\Connector\Modbus\Clients;
use Nette;
use Override;
use function array_map;

/**
 * Base read register request request
 */
abstract class Read implements Clients\Messages\Message
{

	use Nette\SmartObject;

	/**
	 * @param array<Clients\Messages\Pointer\ReadAddress> $addresses
	 */
	public function __construct(
		private readonly array $addresses,
		private readonly int $startAddress,
		private readonly int $quantity,
	)
	{
	}

	/**
	 * @return array<Clients\Messages\Pointer\ReadAddress>
	 */
	public function getAddresses(): array
	{
		return $this->addresses;
	}

	public function getStartAddress(): int
	{
		return $this->startAddress;
	}

	public function getQuantity(): int
	{
		return $this->quantity;
	}

	#[Override]
	public function toArray(): array
	{
		return [
			'addresses' => array_map(
				static fn (Clients\Messages\Pointer\ReadAddress $address): array => $address->toArray(),
				$this->getAddresses(),
			),
			'start_address' => $this->getStartAddress(),
			'quantity' => $this->getQuantity(),
		];
	}

}
