<?php declare(strict_types = 1);

namespace FastyBird\Core\Persistence\DoctrineCrud\Crud;

use Doctrine\ORM;
use Doctrine\Persistence;
use FastyBird\Core\Entities\DoctrineCrud as Entities;
use FastyBird\Core\Exceptions;
use Nette;

/**
 * Base class resolving the Doctrine entity manager and repository for a given entity class
 *
 * @template T of Entities\IEntity
 */
abstract class CrudManager
{

	use Nette\SmartObject;

	/** @var Persistence\ObjectRepository<T> */
	protected Persistence\ObjectRepository $entityRepository;

	/** @var ORM\EntityManagerInterface */
	protected Persistence\ObjectManager $entityManager;

	private bool $flush = true;

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

	public function getFlush(): bool
	{
		return $this->flush;
	}

	public function setFlush(bool $flush): void
	{
		$this->flush = $flush;
	}

}
