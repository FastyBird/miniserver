<?php declare(strict_types = 1);

/**
 * ConditionsRepository.php
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

namespace FastyBird\Module\Triggers\Models\Entities\Conditions;

use Doctrine\ORM;
use Doctrine\Persistence;
use FastyBird\Core\Tools\Exceptions as ToolsExceptions;
use FastyBird\Core\Tools\Helpers as ToolsHelpers;
use FastyBird\Library\DoctrineOrmQuery;
use FastyBird\Module\Triggers\Entities;
use FastyBird\Module\Triggers\Queries;
use Nette;
use function is_array;

/**
 * Condition repository
 *
 * @package        FastyBird:TriggersModule!
 * @subpackage     Models
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class ConditionsRepository
{

	use Nette\SmartObject;

	/** @var array<ORM\EntityRepository<Entities\Conditions\Condition>> */
	private array $repository = [];

	public function __construct(
		private readonly ToolsHelpers\Database $database,
		private readonly Persistence\ManagerRegistry $managerRegistry,
	)
	{
	}

	/**
	 * @param Queries\Entities\FindConditions<Entities\Conditions\Condition> $queryObject
	 * @param class-string<Entities\Conditions\Condition> $type
	 *
	 * @throws ToolsExceptions\InvalidState
	 */
	public function findOneBy(
		Queries\Entities\FindConditions $queryObject,
		string $type = Entities\Conditions\Condition::class,
	): Entities\Conditions\Condition|null
	{
		return $this->database->query(
			fn (): Entities\Conditions\Condition|null => $queryObject->fetchOne($this->getRepository($type)),
		);
	}

	/**
	 * @param Queries\Entities\FindConditions<Entities\Conditions\Condition> $queryObject
	 * @param class-string<Entities\Conditions\Condition> $type
	 *
	 * @return array<Entities\Conditions\Condition>
	 *
	 * @throws ToolsExceptions\InvalidState
	 */
	public function findAllBy(
		Queries\Entities\FindConditions $queryObject,
		string $type = Entities\Conditions\Condition::class,
	): array
	{
		return $this->database->query(
			function () use ($queryObject, $type): array {
				/** @var array<Entities\Conditions\Condition>|DoctrineOrmQuery\ResultSet<Entities\Conditions\Condition> $result */
				$result = $queryObject->fetch($this->getRepository($type));

				if (is_array($result)) {
					return $result;
				}

				/** @var array<Entities\Conditions\Condition> $data */
				$data = $result->toArray();

				return $data;
			},
		);
	}

	/**
	 * @param Queries\Entities\FindConditions<Entities\Conditions\Condition> $queryObject
	 * @param class-string<Entities\Conditions\Condition> $type
	 *
	 * @return DoctrineOrmQuery\ResultSet<Entities\Conditions\Condition>
	 *
	 * @throws ToolsExceptions\InvalidState
	 */
	public function getResultSet(
		Queries\Entities\FindConditions $queryObject,
		string $type = Entities\Conditions\Condition::class,
	): DoctrineOrmQuery\ResultSet
	{
		return $this->database->query(
			function () use ($queryObject, $type): DoctrineOrmQuery\ResultSet {
				/** @var DoctrineOrmQuery\ResultSet<Entities\Conditions\Condition> $result */
				$result = $queryObject->fetch($this->getRepository($type));

				return $result;
			},
		);
	}

	/**
	 * @param class-string<Entities\Conditions\Condition> $type
	 *
	 * @return ORM\EntityRepository<Entities\Conditions\Condition>
	 */
	private function getRepository(string $type): ORM\EntityRepository
	{
		if (!isset($this->repository[$type])) {
			$this->repository[$type] = $this->managerRegistry->getRepository($type);
		}

		return $this->repository[$type];
	}

}
