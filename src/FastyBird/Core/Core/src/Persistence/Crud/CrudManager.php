<?php declare(strict_types = 1);

namespace FastyBird\Core\Persistence\Crud;

use Doctrine\ORM;
use Doctrine\Persistence;
use FastyBird\Core\Exceptions;
use FastyBird\Core\Persistence\Entities;

/**
 * Base class resolving the Doctrine entity manager and repository for a given entity class
 *
 * @template T of Entities\CrudEntity
 */
abstract class CrudManager
{

	/** @var Persistence\ObjectRepository<T> */
	protected Persistence\ObjectRepository $entityRepository;

	/** @var ORM\EntityManagerInterface */
	protected Persistence\ObjectManager $entityManager;

	public protected(set) bool $flush = true;

	/**
	 * @param class-string<T> $entityName
	 *
	 * @throws Exceptions\InvalidState
	 */
	public function __construct(
		protected string $entityName,
		Persistence\ManagerRegistry $managerRegistry,
	)
	{
		$entityManager = $managerRegistry->getManagerForClass($entityName);

		if (!$entityManager instanceof ORM\EntityManagerInterface) {
			throw new Exceptions\InvalidState('Entity manager could not be loaded');
		}

		$this->entityManager = $entityManager;
		$this->entityRepository = $this->entityManager->getRepository($entityName);
	}

}
