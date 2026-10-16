<?php declare(strict_types = 1);

namespace FastyBird\Module\Triggers\Tests\Fixtures\Dummy;

use FastyBird\Module\Triggers\Models;
use FastyBird\Module\Triggers\States;
use Override;
use Ramsey\Uuid;

/**
 * Stands for a action state storage: records what it is asked to write, and answers with one
 * state per id
 */
final class DummyActionsWriter implements Models\States\ActionsWriter
{

	/** @var list<array{string, string, array<string, mixed>}> */
	public array $written = [];

	/** @var array<string, DummyActionState> */
	private array $states = [];

	public function state(Uuid\UuidInterface $id): DummyActionState
	{
		return $this->states[$id->toString()] ??= new DummyActionState($id);
	}

	/**
	 * @param array<string, mixed> $values
	 */
	#[Override]
	public function create(Uuid\UuidInterface $id, array $values): States\Action
	{
		$this->written[] = ['create', $id->toString(), $values];

		return $this->state($id);
	}

	/**
	 * @param array<string, mixed> $values
	 */
	#[Override]
	public function update(Uuid\UuidInterface $id, array $values): States\Action
	{
		$this->written[] = ['update', $id->toString(), $values];

		return $this->state($id);
	}

	#[Override]
	public function delete(Uuid\UuidInterface $id): bool
	{
		return true;
	}

}
