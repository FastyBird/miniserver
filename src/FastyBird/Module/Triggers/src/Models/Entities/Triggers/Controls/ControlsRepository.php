<?php declare(strict_types = 1);

/**
 * ControlsRepository.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:TriggersModule!
 * @subpackage     Models
 * @since          1.0.0
 *
 * @date           01.10.21
 */

namespace FastyBird\Module\Triggers\Models\Entities\Triggers\Controls;

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
 * Trigger control structure repository
 *
 * @package        FastyBird:TriggersModule!
 * @subpackage     Models
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class ControlsRepository
{

	use Nette\SmartObject;

	/** @var ORM\EntityRepository<Entities\Triggers\Controls\Control>|null */
	private ORM\EntityRepository|null $repository = null;

	public function __construct(
		private readonly ToolsHelpers\Database $database,
		private readonly Persistence\ManagerRegistry $managerRegistry,
	)
	{
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	public function findOneBy(
		Queries\Entities\FindTriggerControls $queryObject,
	): Entities\Triggers\Controls\Control|null
	{
		return $this->database->query(
			fn (): Entities\Triggers\Controls\Control|null => $queryObject->fetchOne($this->getRepository()),
		);
	}

	/**
	 * @return array<Entities\Triggers\Controls\Control>
	 *
	 * @throws Exceptions\InvalidState
	 */
	public function findAllBy(Queries\Entities\FindTriggerControls $queryObject): array
	{
		return $this->database->query(
			function () use ($queryObject): array {
				/** @var array<Entities\Triggers\Controls\Control>|DoctrineOrmQuery\ResultSet<Entities\Triggers\Controls\Control> $result */
				$result = $queryObject->fetch($this->getRepository());

				if (is_array($result)) {
					return $result;
				}

				/** @var array<Entities\Triggers\Controls\Control> $data */
				$data = $result->toArray();

				return $data;
			},
		);
	}

	/**
	 * @return DoctrineOrmQuery\ResultSet<Entities\Triggers\Controls\Control>
	 *
	 * @throws Exceptions\InvalidState
	 */
	public function getResultSet(
		Queries\Entities\FindTriggerControls $queryObject,
	): DoctrineOrmQuery\ResultSet
	{
		return $this->database->query(
			function () use ($queryObject): DoctrineOrmQuery\ResultSet {
				/** @var DoctrineOrmQuery\ResultSet<Entities\Triggers\Controls\Control> $result */
				$result = $queryObject->fetch($this->getRepository());

				return $result;
			},
		);
	}

	/**
	 * @param class-string<Entities\Triggers\Controls\Control> $type
	 *
	 * @return ORM\EntityRepository<Entities\Triggers\Controls\Control>
	 */
	private function getRepository(string $type = Entities\Triggers\Controls\Control::class): ORM\EntityRepository
	{
		if ($this->repository === null) {
			$this->repository = $this->managerRegistry->getRepository($type);
		}

		return $this->repository;
	}

}
