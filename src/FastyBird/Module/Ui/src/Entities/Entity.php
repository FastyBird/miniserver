<?php declare(strict_types = 1);

namespace FastyBird\Module\Ui\Entities;

use FastyBird\Core\Persistence\Entities;
use FastyBird\Core\Values\Types\Sources;
use Ramsey\Uuid;

/**
 * Base entity interface
 */
interface Entity extends Entities\CrudEntity
{

	public function getId(): Uuid\UuidInterface;

	/**
	 * @return array<string, mixed>
	 */
	public function toArray(): array;

	public function getSource(): Sources\Source;

}
