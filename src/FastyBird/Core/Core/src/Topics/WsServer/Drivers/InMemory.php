<?php declare(strict_types = 1);

namespace FastyBird\Core\Topics\WsServer\Drivers;

use FastyBird\Core\Entities\WsServer\Topics as Entities;
use Nette;
use Override;
use function array_values;

/**
 * Classic memory topic storage driver
 */
final class InMemory implements IDriver
{

	/**
	 * Implement nette smart magic
	 */
	use Nette\SmartObject;

	private array $elements;

	public function __construct()
	{
		$this->elements = [];
	}

	#[Override]
	public function fetch(string $id): Entities\ITopic|bool
	{
		if (!$this->contains($id)) {
			return false;
		}

		return $this->elements[$id];
	}

	/**
	 * {@inheritDoc}
	 */
	#[Override]
	public function fetchAll(): array
	{
		return array_values($this->elements);
	}

	#[Override]
	public function contains(string $id): bool
	{
		return isset($this->elements[$id]);
	}

	/**
	 * {@inheritDoc}
	 */
	#[Override]
	public function save(string $id, $data, int $lifeTime = 0): bool
	{
		$this->elements[$id] = $data; // Lifetime is not supported

		return true;
	}

	#[Override]
	public function delete(string $id): bool
	{
		unset($this->elements[$id]);

		return true;
	}

}
