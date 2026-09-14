<?php declare(strict_types = 1);

/**
 * IEntityCreator.php
 *
 * @copyright      More in LICENSE.md
 * @license        https://www.ipublikuj.eu
 * @author         Adam Kadlec <adam.kadlec@ipublikuj.eu>
 * @package        iPublikuj:DoctrineCrud!
 * @subpackage     Crud
 * @since          1.0.0
 *
 * @date           29.01.14
 */

namespace FastyBird\Library\DoctrineCrud\Crud\Create;

use FastyBird\Library\DoctrineCrud\Entities;
use FastyBird\Library\DoctrineCrud\Mapping;

/**
 * Doctrine CRUD entity creator factory
 *
 * @template T of Entities\IEntity
 *
 * @package        iPublikuj:DoctrineCrud!
 * @subpackage     Crud
 *
 * @author         Adam Kadlec <adam.kadlec@ipublikuj.eu>
 */
interface IEntityCreator
{

	/**
	 * @param class-string<T> $entityName
	 *
	 * @return EntityCreator<T>
	 */
	public function create(string $entityName, Mapping\IEntityMapper $entityMapper): EntityCreator;

}
