<?php declare(strict_types = 1);

namespace FastyBird\Core\Tests\Fixtures\Dummy;

use FastyBird\Core\WebSockets\Entities\Topics;
use FastyBird\Core\WebSockets\Topics\Drivers;
use Override;

/**
 * A topics storage driver that stores nothing and records the identifiers it is asked about,
 * used to see which driver the container wires into the topics storage
 */
final class DummyTopicsDriver implements Drivers\Driver
{

	/** @var array<string> */
	private array $containsCalls = [];

	#[Override]
	public function fetch(string $id): Topics\Topic|bool
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
	public function contains(string $id): bool
	{
		$this->containsCalls[] = $id;

		return false;
	}

	#[Override]
	public function save(string $id, mixed $data, int $lifeTime = 0): bool
	{
		return true;
	}

	#[Override]
	public function delete(string $id): bool
	{
		return false;
	}

	/**
	 * @return array<string>
	 */
	public function getContainsCalls(): array
	{
		return $this->containsCalls;
	}

}
