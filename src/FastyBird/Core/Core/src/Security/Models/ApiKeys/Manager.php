<?php declare(strict_types = 1);

namespace FastyBird\Core\Security\Models\ApiKeys;

use Doctrine\DBAL;
use FastyBird\Core\Exceptions as CoreExceptions;
use FastyBird\Core\Persistence\Crud;
use FastyBird\Core\Persistence\Exceptions as PersistenceExceptions;
use FastyBird\Core\Security\Entities\ApiKeys;
use Nette\Utils;
use ReflectionException;
use function assert;

/**
 * Security API keys entities manager
 */
final class Manager
{

	/** @var Crud\EntityCrud<ApiKeys\ApiKey>|null */
	private Crud\EntityCrud|null $entityCrud = null;

	/**
	 * @param Crud\CrudFactory<ApiKeys\ApiKey> $entityCrudFactory
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
	 * @throws ReflectionException
	 */
	public function create(Utils\ArrayHash $values): ApiKeys\ApiKey
	{
		$entity = $this->getEntityCrud()->getEntityCreator()->create($values);
		assert($entity instanceof ApiKeys\ApiKey);

		return $entity;
	}

	/**
	 * @throws DBAL\Exception\UniqueConstraintViolationException
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws PersistenceExceptions\EntityCreation
	 * @throws ReflectionException
	 */
	public function update(
		ApiKeys\ApiKey $entity,
		Utils\ArrayHash $values,
	): ApiKeys\ApiKey
	{
		$entity = $this->getEntityCrud()->getEntityUpdater()->update($values, $entity);
		assert($entity instanceof ApiKeys\ApiKey);

		return $entity;
	}

	/**
	 * @return Crud\EntityCrud<ApiKeys\ApiKey>
	 */
	public function getEntityCrud(): Crud\EntityCrud
	{
		if ($this->entityCrud === null) {
			$this->entityCrud = $this->entityCrudFactory->create(ApiKeys\ApiKey::class);
		}

		return $this->entityCrud;
	}

}
