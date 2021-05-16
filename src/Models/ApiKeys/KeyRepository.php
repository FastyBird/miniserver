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

use Doctrine\Common;
use Doctrine\Persistence;
use FastyBird\MiniServer\Entities;
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

	/** @var Common\Persistence\ManagerRegistry */
	private Common\Persistence\ManagerRegistry $managerRegistry;

	/** @var Persistence\ObjectRepository<Entities\ApiKeys\Key>|null */
	private ?Persistence\ObjectRepository $repository = null;

	public function __construct(Common\Persistence\ManagerRegistry $managerRegistry)
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
	 * @return Persistence\ObjectRepository<Entities\ApiKeys\Key>
	 */
	private function getRepository(): Persistence\ObjectRepository
	{
		if ($this->repository === null) {
			$this->repository = $this->managerRegistry->getRepository(Entities\ApiKeys\Key::class);
		}

		return $this->repository;
	}

}
