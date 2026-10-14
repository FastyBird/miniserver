<?php declare(strict_types = 1);

namespace FastyBird\Module\Triggers\Tests\Fixtures\Dummy;

use FastyBird\Module\Triggers\States;
use Override;
use Ramsey\Uuid;

final readonly class DummyConditionState implements States\Condition
{

	public function __construct(private Uuid\UuidInterface $id)
	{
	}

	#[Override]
	public function getId(): Uuid\UuidInterface
	{
		return $this->id;
	}

	#[Override]
	public function isFulfilled(): bool
	{
		return false;
	}

}
