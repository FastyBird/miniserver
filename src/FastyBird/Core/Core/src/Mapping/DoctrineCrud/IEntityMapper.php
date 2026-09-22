<?php declare(strict_types = 1);

namespace FastyBird\Core\Mapping\DoctrineCrud;

use FastyBird\Core\Entities\DoctrineCrud as Entities;
use Nette\Utils;

/**
 * Doctrine CRUD entity mapper interface
 */
interface IEntityMapper
{

	/**
	 * Annotation strings
	 */
	public const string ANNOTATION_REQUIRED = 'required';

	public const string ANNOTATION_WRITABLE = 'writable';

	public function fillEntity(
		Utils\ArrayHash $values,
		Entities\IEntity $entity,
		bool $isNew = false,
	): Entities\IEntity;

}
