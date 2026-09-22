<?php declare(strict_types = 1);

namespace FastyBird\Core\Persistence\DoctrineCrud\Crud\Delete;

use Doctrine\DBAL;
use Doctrine\ORM;
use FastyBird\Core\Entities\DoctrineCrud as Entities;
use FastyBird\Core\Exceptions;
use FastyBird\Core\Persistence\DoctrineCrud\Crud;
use Nette\Utils;

/**
 * Removes an entity from persistence inside its own transaction
 *
 * @template   T of Entities\IEntity
 * @extends    Crud\CrudManager<T>
 */
final class EntityDeleter extends Crud\CrudManager
{

	/** @var array<callable(Entities\IEntity): void> */
	public array $beforeAction = [];

	/** @var array<callable(): void> */
	public array $afterAction = [];

	/**
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 */
	public function delete(Entities\IEntity|int|string $entity): bool
	{
		if (!$entity instanceof Entities\IEntity) {
			$entity = $this->entityRepository->find($entity);
		}

		if (!$entity instanceof Entities\IEntity) {
			throw new Exceptions\InvalidArgument('Entity not found.');
		}

		Utils\Arrays::invoke($this->beforeAction, $entity);

		try {
			$this->entityManager->getConnection()->beginTransaction();

			$this->entityManager->remove($entity);

			if ($this->getFlush() === true) {
				$this->entityManager->flush();
			}

			$this->entityManager->getConnection()->commit();
		} catch (ORM\Exception\ORMException | DBAL\Exception $ex) {
			throw new Exceptions\InvalidState('Entity could not be deleted', $ex->getCode(), $ex);
		}

		Utils\Arrays::invoke($this->afterAction);

		return true;
	}

}
