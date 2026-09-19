<?php declare(strict_types = 1);

namespace FastyBird\Library\WebSockets\Clients\Drivers;

use FastyBird\Library\WebSockets\Entities;
use function array_values;

/**
 * Classic memory client storage driver
 *
 * @package        iPublikuj:WebSockets!
 * @subpackage     Clients
 *
 * @author         Adam Kadlec <adam.kadlec@ipublikuj.eu>
 */
final class InMemory implements IDriver
{

	private array $elements;

	public function __construct()
	{
		$this->elements = [];
	}

	public function fetch(int $id): Entities\Clients\IClient|bool
	{
		if (!$this->contains($id)) {
			return false;
		}

		return $this->elements[$id];
	}

	/**
	 * {@inheritDoc}
	 */
	public function fetchAll(): array
	{
		return array_values($this->elements);
	}

	public function contains(int $id): bool
	{
		return isset($this->elements[$id]);
	}

	/**
	 * {@inheritDoc}
	 */
	public function save(int $id, $data, int $lifeTime = 0): bool
	{
		$this->elements[$id] = $data; // Lifetime is not supported

		return true;
	}

	public function delete(int $id): bool
	{
		unset($this->elements[$id]);

		return true;
	}

}
