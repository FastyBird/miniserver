<?php declare(strict_types = 1);

namespace FastyBird\Core\Tests\Fixtures\Dummy;

use FastyBird\Core\WebSockets\Clients\Drivers;
use FastyBird\Core\WebSockets\Entities;
use Override;

/**
 * A clients storage driver that stores nothing and records the identifiers it is asked about,
 * used to see which driver the container wires into the clients storage
 */
final class DummyClientsDriver implements Drivers\Driver
{

	/** @var array<int> */
	private array $containsCalls = [];

	#[Override]
	public function fetch(int $id): Entities\Client|bool
	{
		return false;
	}

	/**
	 * {@inheritDoc}
	 */
	#[Override]
	public function fetchAll(): array
	{
		return [];
	}

	#[Override]
	public function contains(int $id): bool
	{
		$this->containsCalls[] = $id;

		return false;
	}

	#[Override]
	public function save(int $id, mixed $data, int $lifeTime = 0): bool
	{
		return true;
	}

	#[Override]
	public function delete(int $id): bool
	{
		return false;
	}

	/**
	 * @return array<int>
	 */
	public function getContainsCalls(): array
	{
		return $this->containsCalls;
	}

}
