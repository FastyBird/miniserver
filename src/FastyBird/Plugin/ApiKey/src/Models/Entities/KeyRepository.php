<?php declare(strict_types = 1);

namespace FastyBird\Plugin\ApiKey\Models\Entities;

use Doctrine\ORM;
use Doctrine\Persistence;
use FastyBird\Plugin\ApiKey\Entities;

/**
 * API key repository
 */
final class KeyRepository
{

	/** @var array<ORM\EntityRepository<Entities\Key>> */
	private array $repository = [];

	public function __construct(private readonly Persistence\ManagerRegistry $managerRegistry)
	{
	}

	public function findOneByIdentifier(string $identifier): Entities\Key|null
	{
		return $this->getRepository()->findOneBy(['id' => $identifier]);
	}

	public function findOneByKey(string $key): Entities\Key|null
	{
		return $this->getRepository()->findOneBy(['key' => $key]);
	}

	/**
	 * @param class-string<Entities\Key> $type
	 *
	 * @return ORM\EntityRepository<Entities\Key>
	 */
	private function getRepository(string $type = Entities\Key::class): ORM\EntityRepository
	{
		if (!isset($this->repository[$type])) {
			$this->repository[$type] = $this->managerRegistry->getRepository($type);
		}

		return $this->repository[$type];
	}

}
