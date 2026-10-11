<?php declare(strict_types = 1);

namespace FastyBird\Module\Accounts\Entities;

use FastyBird\Core\Values\Types\Sources;
use Ramsey\Uuid;

/**
 * Entity base trait
 *
 * @property-read Uuid\UuidInterface $id
 */
trait HasEntityId
{

	public function getId(): Uuid\UuidInterface
	{
		return $this->id;
	}

	public function getSource(): Sources\Source
	{
		return Sources\Module::ACCOUNTS;
	}

}
