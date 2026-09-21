<?php declare(strict_types = 1);

/**
 * TriggersRepository.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:TriggersModule!
 * @subpackage     Models
 * @since          1.0.0
 *
 * @date           04.04.20
 */

namespace FastyBird\Module\Triggers\Models\Entities\Triggers;

use Doctrine\ORM;
use Doctrine\Persistence;
use FastyBird\Core\Exceptions;
use FastyBird\Core\Helpers\Tools as ToolsHelpers;
use FastyBird\Core\Persistence\DoctrineOrmQuery;
use FastyBird\Module\Triggers\Entities;
use FastyBird\Module\Triggers\Queries;
use Nette;
use function is_array;

/**
 * Trigger repository
 *
 * @package        FastyBird:TriggersModule!
 * @subpackage     Models
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class TriggersRepository
{

	use Nette\SmartObject;

	/** @var array<ORM\EntityRepository<Entities\Triggers\Trigger>> */
	private array $repository = [];

	public function __construct(
		private readonly ToolsHelpers\Database $database,
		private readonly Persistence\ManagerRegistry $managerRegistry,
	)
	{
	}

	/**
	 * @param class-string<Entities\Triggers\Trigger> $type
	 *
	 * @throws Exceptions\InvalidState
	 */
	public function findOneBy(
		Queries\Entities\FindTriggers $queryObject,
		string $type = Entities\Triggers\Trigger::class,
	): Entities\Triggers\Trigger|null
	{
		return $this->database->query(
			fn (): Entities\Triggers\Trigger|null => $queryObject->fetchOne($this->getRepository($type)),
		);
	}

	/**
	 * @param class-string<Entities\Triggers\Trigger> $type
	 *
	 * @return array<Entities\Triggers\Trigger>
	 *
	 * @throws Exceptions\InvalidState
	 */
	public function findAllBy(
		Queries\Entities\FindTriggers $queryObject,
		string $type = Entities\Triggers\Trigger::class,
	): array
	{
		return $this->database->query(
			function () use ($queryObject, $type): array {
				/** @var array<Entities\Triggers\Trigger>|DoctrineOrmQuery\ResultSet<Entities\Triggers\Trigger> $result */
				$result = $queryObject->fetch($this->getRepository($type));

				if (is_array($result)) {
					return $result;
				}

				/** @var array<Entities\Triggers\Trigger> $data */
				$data = $result->toArray();

				return $data;
			},
		);
	}

	/**
	 * @param class-string<Entities\Triggers\Trigger> $type
	 *
	 * @return DoctrineOrmQuery\ResultSet<Entities\Triggers\Trigger>
	 *
	 * @throws Exceptions\InvalidState
	 */
	public function getResultSet(
		Queries\Entities\FindTriggers $queryObject,
		string $type = Entities\Triggers\Trigger::class,
	): DoctrineOrmQuery\ResultSet
	{
		return $this->database->query(
			function () use ($queryObject, $type): DoctrineOrmQuery\ResultSet {
				/** @var DoctrineOrmQuery\ResultSet<Entities\Triggers\Trigger> $result */
				$result = $queryObject->fetch($this->getRepository($type));

				return $result;
			},
		);
	}

	/**
	 * @param class-string<Entities\Triggers\Trigger> $type
	 *
	 * @return ORM\EntityRepository<Entities\Triggers\Trigger>
	 */
	private function getRepository(string $type): ORM\EntityRepository
	{
		if (!isset($this->repository[$type])) {
			$this->repository[$type] = $this->managerRegistry->getRepository($type);
		}

		return $this->repository[$type];
	}

}
