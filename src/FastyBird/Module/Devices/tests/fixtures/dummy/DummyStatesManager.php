<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices\Tests\Fixtures\Dummy;

use FastyBird\Module\Devices\States;
use Ramsey\Uuid;
use RuntimeException;

class DummyStatesManager
{

	/**
	 * @param array<string, mixed> $values
	 *
	 * @throws RuntimeException
	 */
	public function create(Uuid\UuidInterface $id, array $values): States\Property
	{
		throw new RuntimeException('This is dummy service');
	}

	/**
	 * @param array<string, mixed> $values
	 *
	 * @throws RuntimeException
	 */
	public function update(States\Property $state, array $values): States\Property
	{
		throw new RuntimeException('This is dummy service');
	}

	/**
	 * @param array<string, mixed> $values
	 *
	 * @throws RuntimeException
	 */
	public function updateState(States\Property $state, array $values): States\Property
	{
		throw new RuntimeException('This is dummy service');
	}

	/**
	 * @throws RuntimeException
	 */
	public function delete(States\Property $state): bool
	{
		throw new RuntimeException('This is dummy service');
	}

}
