<?php declare(strict_types = 1);

namespace FastyBird\Bridge\DevicesModuleUiModule\Tests\Fixtures\Dummy;

use FastyBird\Module\Devices\States as DevicesStates;
use Ramsey\Uuid;
use RuntimeException;

class DummyStatesManager
{

	/**
	 * @param array<string, mixed> $values
	 *
	 * @throws RuntimeException
	 */
	public function create(Uuid\UuidInterface $id, array $values): DevicesStates\Property
	{
		throw new RuntimeException('This is dummy service');
	}

	/**
	 * @param array<string, mixed> $values
	 *
	 * @throws RuntimeException
	 */
	public function update(DevicesStates\Property $state, array $values): DevicesStates\Property
	{
		throw new RuntimeException('This is dummy service');
	}

	/**
	 * @param array<string, mixed> $values
	 *
	 * @throws RuntimeException
	 */
	public function updateState(DevicesStates\Property $state, array $values): DevicesStates\Property
	{
		throw new RuntimeException('This is dummy service');
	}

	/**
	 * @throws RuntimeException
	 */
	public function delete(DevicesStates\Property $state): bool
	{
		throw new RuntimeException('This is dummy service');
	}

}
