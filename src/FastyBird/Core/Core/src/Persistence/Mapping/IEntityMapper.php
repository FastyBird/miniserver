<?php declare(strict_types = 1);

namespace FastyBird\Core\Persistence\Mapping;

use FastyBird\Core\Persistence\Entities;
use Nette\Utils;

/**
 * Interface for mapping request values onto an entity's #[Crud]-marked properties
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
		Entities\CrudEntity $entity,
		bool $isNew = false,
	): Entities\CrudEntity;

}
