<?php declare(strict_types = 1);

namespace FastyBird\Core\Security\Models\Tokens;

use Doctrine\DBAL;
use FastyBird\Core\Exceptions as CoreExceptions;
use FastyBird\Core\Persistence\Crud;
use FastyBird\Core\Persistence\Exceptions as PersistenceExceptions;
use FastyBird\Core\Security\Entities\Tokens;
use Nette\Utils;
use function assert;

/**
 * Security tokens entities manager
 */
final class Manager
{

	/** @var Crud\IEntityCrud<Tokens\Token>|null */
	private Crud\IEntityCrud|null $entityCrud = null;

	/**
	 * @param Crud\CrudFactory<Tokens\Token> $entityCrudFactory
	 */
	public function __construct(
		private readonly Crud\CrudFactory $entityCrudFactory,
	)
	{
	}

	/**
	 * @throws DBAL\Exception\UniqueConstraintViolationException
	 * @throws PersistenceExceptions\EntityCreation
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 */
	public function create(Utils\ArrayHash $values): Tokens\Token
	{
		$entity = $this->getEntityCrud()->getEntityCreator()->create($values);
		assert($entity instanceof Tokens\Token);

		return $entity;
	}

	/**
	 * @throws DBAL\Exception\UniqueConstraintViolationException
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 */
	public function update(
		Tokens\Token $entity,
		Utils\ArrayHash $values,
	): Tokens\Token
	{
		$entity = $this->getEntityCrud()->getEntityUpdater()->update($values, $entity);
		assert($entity instanceof Tokens\Token);

		return $entity;
	}

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 */
	public function delete(Tokens\Token $entity): bool
	{
		// Delete entity from database
		return $this->getEntityCrud()->getEntityDeleter()->delete($entity);
	}

	/**
	 * @return Crud\IEntityCrud<Tokens\Token>
	 */
	public function getEntityCrud(): Crud\IEntityCrud
	{
		if ($this->entityCrud === null) {
			$this->entityCrud = $this->entityCrudFactory->create(Tokens\Token::class);
		}

		return $this->entityCrud;
	}

}
