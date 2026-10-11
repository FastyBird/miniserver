<?php declare(strict_types = 1);

namespace FastyBird\Module\Triggers\Entities;

use FastyBird\Core\Values\Types\Sources;
use Ramsey\Uuid;

/**
 * Entity base trait
 *
 * @property-read Uuid\UuidInterface $id
 */
trait TEntity
{

	public function getId(): Uuid\UuidInterface
	{
		return $this->id;
	}

	public function getPlainId(): string
	{
		return $this->id->toString();
	}

	public function getSource(): Sources\Source
	{
		return Sources\Module::TRIGGERS;
	}

}
