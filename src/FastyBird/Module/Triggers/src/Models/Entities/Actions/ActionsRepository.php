<?php declare(strict_types = 1);

/**
 * ActionsRepository.php
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

namespace FastyBird\Module\Triggers\Models\Entities\Actions;

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
 * Actions repository
 *
 * @package        FastyBird:TriggersModule!
 * @subpackage     Models
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class ActionsRepository
{

	use Nette\SmartObject;

	/** @var array<ORM\EntityRepository<Entities\Actions\Action>> */
	private array $repository = [];

	public function __construct(
		private readonly ToolsHelpers\Database $database,
		private readonly Persistence\ManagerRegistry $managerRegistry,
	)
	{
	}

	/**
	 * @param Queries\Entities\FindActions<Entities\Actions\Action> $queryObject
	 * @param class-string<Entities\Actions\Action> $type
	 *
	 * @throws ToolsExceptions\InvalidState
	 */
	public function findOneBy(
		Queries\Entities\FindActions $queryObject,
		string $type = Entities\Actions\Action::class,
	): Entities\Actions\Action|null
	{
		return $this->database->query(
			fn (): Entities\Actions\Action|null => $queryObject->fetchOne($this->getRepository($type)),
		);
	}

	/**
	 * @param Queries\Entities\FindActions<Entities\Actions\Action> $queryObject
	 * @param class-string<Entities\Actions\Action> $type
	 *
	 * @return array<Entities\Actions\Action>
	 *
	 * @throws ToolsExceptions\InvalidState
	 */
	public function findAllBy(
		Queries\Entities\FindActions $queryObject,
		string $type = Entities\Actions\Action::class,
	): array
	{
		return $this->database->query(
			function () use ($queryObject, $type): array {
				/** @var array<Entities\Actions\Action>|DoctrineOrmQuery\ResultSet<Entities\Actions\Action> $result */
				$result = $queryObject->fetch($this->getRepository($type));

				if (is_array($result)) {
					return $result;
				}

				/** @var array<Entities\Actions\Action> $data */
				$data = $result->toArray();

				return $data;
			},
		);
	}

	/**
	 * @param Queries\Entities\FindActions<Entities\Actions\Action> $queryObject
	 * @param class-string<Entities\Actions\Action> $type
	 *
	 * @return DoctrineOrmQuery\ResultSet<Entities\Actions\Action>
	 *
	 * @throws ToolsExceptions\InvalidState
	 */
	public function getResultSet(
		Queries\Entities\FindActions $queryObject,
		string $type = Entities\Actions\Action::class,
	): DoctrineOrmQuery\ResultSet
	{
		return $this->database->query(
			function () use ($queryObject, $type): DoctrineOrmQuery\ResultSet {
				/** @var DoctrineOrmQuery\ResultSet<Entities\Actions\Action> $result */
				$result = $queryObject->fetch($this->getRepository($type));

				return $result;
			},
		);
	}

	/**
	 * @param class-string<Entities\Actions\Action> $type
	 *
	 * @return ORM\EntityRepository<Entities\Actions\Action>
	 */
	private function getRepository(string $type): ORM\EntityRepository
	{
		if (!isset($this->repository[$type])) {
			$this->repository[$type] = $this->managerRegistry->getRepository($type);
		}

		return $this->repository[$type];
	}

}
