<?php declare(strict_types = 1);

namespace FastyBird\Core\Persistence\Crud\Delete;

use Doctrine\DBAL;
use Doctrine\ORM;
use FastyBird\Core\Exceptions;
use FastyBird\Core\Persistence\Crud;
use FastyBird\Core\Persistence\Entities;

/**
 * Removes an entity from persistence inside its own transaction
 *
 * @template   T of Entities\CrudEntity
 * @extends    Crud\CrudManager<T>
 */
final class EntityDeleter extends Crud\CrudManager
{

	/**
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 */
	public function delete(Entities\CrudEntity|int|string $entity): bool
	{
		if (!$entity instanceof Entities\CrudEntity) {
			$entity = $this->entityRepository->find($entity);
		}

		if (!$entity instanceof Entities\CrudEntity) {
			throw new Exceptions\InvalidArgument('Entity not found.');
		}

		try {
			$this->entityManager->getConnection()->beginTransaction();

			$this->entityManager->remove($entity);

			if ($this->flush === true) {
				$this->entityManager->flush();
			}

			$this->entityManager->getConnection()->commit();
		} catch (ORM\Exception\ORMException | DBAL\Exception $ex) {
			throw new Exceptions\InvalidState('Entity could not be deleted', $ex->getCode(), $ex);
		}

		return true;
	}

}
