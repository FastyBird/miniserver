<?php declare(strict_types = 1);

/**
 * KeyRepository.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:MiniServer!
 * @subpackage     Models
 * @since          0.1.0
 *
 * @date           16.05.21
 */

namespace FastyBird\MiniServer\Models\ApiKeys;

use Doctrine\ORM;
use Doctrine\Persistence;
use FastyBird\MiniServer\Entities;
use FastyBird\MiniServer\Exceptions;
use Nette;

/**
 * API key repository
 *
 * @package        FastyBird:MiniServer!
 * @subpackage     Models
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class KeyRepository implements IKeyRepository
{

	use Nette\SmartObject;

	/**
	 * @var ORM\EntityRepository|null
	 *
	 * @phpstan-var ORM\EntityRepository<Entities\ApiKeys\IKey>|null
	 */
	private ?Persistence\ObjectRepository $repository = null;

	/** @var Persistence\ManagerRegistry */
	private Persistence\ManagerRegistry $managerRegistry;

	public function __construct(Persistence\ManagerRegistry $managerRegistry)
	{
		$this->managerRegistry = $managerRegistry;
	}

	/**
	 * {@inheritDoc}
	 */
	public function findOneByIdentifier(string $identifier): ?Entities\ApiKeys\IKey
	{
		/** @var Entities\ApiKeys\IKey|null $key */
		$key = $this->getRepository()->findOneBy(['id' => $identifier]);

		return $key;
	}

	/**
	 * {@inheritDoc}
	 */
	public function findOneByKey(string $key): ?Entities\ApiKeys\IKey
	{
		/** @var Entities\ApiKeys\IKey|null $key */
		$key = $this->getRepository()->findOneBy(['key' => $key]);

		return $key;
	}

	/**
	 * @param string $type
	 *
	 * @return ORM\EntityRepository
	 *
	 * @phpstan-param class-string $type
	 *
	 * @phpstan-return ORM\EntityRepository<Entities\ApiKeys\IKey>
	 */
	private function getRepository(string $type = Entities\ApiKeys\Key::class): ORM\EntityRepository
	{
		if ($this->repository === null) {
			$repository = $this->managerRegistry->getRepository($type);

			if (!$repository instanceof ORM\EntityRepository) {
				throw new Exceptions\InvalidStateException('Entity repository could not be loaded');
			}

			$this->repository = $repository;
		}

		return $this->repository;
	}

}
