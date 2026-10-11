<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices\Documents;

use FastyBird\Core\Documents;
use FastyBird\Core\Values\Types\Sources;
use Ramsey\Uuid;

/**
 * Base document interface
 */
interface Document extends Documents\Document
{

	public function getId(): Uuid\UuidInterface;

	/**
	 * @return array<string, mixed>
	 */
	public function toArray(): array;

	public function getSource(): Sources\Source;

}
