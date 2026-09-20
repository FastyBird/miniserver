<?php declare(strict_types = 1);

/**
 * ControlManager.php
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

use Doctrine\DBAL;
use FastyBird\Core\Exceptions\DoctrineCrud as DoctrineCrudExceptions;
use FastyBird\Core\Persistence\DoctrineCrud\Crud as DoctrineCrudCrud;
use FastyBird\Module\Triggers\Entities;
use FastyBird\Module\Triggers\Models;
use Nette;
use Nette\Utils;
use function assert;

/**
 * Triggers controls entities manager
 *
 * @package        FastyBird:TriggersModule!
 * @subpackage     Models
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class ControlsManager
{

	use Nette\SmartObject;

	/** @var DoctrineCrudCrud\IEntityCrud<Entities\Triggers\Controls\Control>|null */
	private DoctrineCrudCrud\IEntityCrud|null $entityCrud = null;

	/**
	 * @param DoctrineCrudCrud\IEntityCrudFactory<Entities\Triggers\Controls\Control> $entityCrudFactory
	 */
	public function __construct(
		private readonly DoctrineCrudCrud\IEntityCrudFactory $entityCrudFactory,
	)
	{
	}

	/**
	 * @throws DBAL\Exception\UniqueConstraintViolationException
	 * @throws DoctrineCrudExceptions\EntityCreation
	 * @throws DoctrineCrudExceptions\InvalidArgument
	 * @throws DoctrineCrudExceptions\InvalidState
	 */
	public function create(
		Utils\ArrayHash $values,
	): Entities\Triggers\Controls\Control
	{
		$entity = $this->getEntityCrud()->getEntityCreator()->create($values);
		assert($entity instanceof Entities\Triggers\Controls\Control);

		return $entity;
	}

	/**
	 * @throws DBAL\Exception\UniqueConstraintViolationException
	 * @throws DoctrineCrudExceptions\InvalidArgument
	 * @throws DoctrineCrudExceptions\InvalidState
	 */
	public function update(
		Entities\Triggers\Controls\Control $entity,
		Utils\ArrayHash $values,
	): Entities\Triggers\Controls\Control
	{
		$entity = $this->getEntityCrud()->getEntityUpdater()->update($values, $entity);
		assert($entity instanceof Entities\Triggers\Controls\Control);

		return $entity;
	}

	/**
	 * @throws DoctrineCrudExceptions\InvalidArgument
	 * @throws DoctrineCrudExceptions\InvalidState
	 */
	public function delete(Entities\Triggers\Controls\Control $entity): bool
	{
		// Delete entity from database
		return $this->getEntityCrud()->getEntityDeleter()->delete($entity);
	}

	/**
	 * @return DoctrineCrudCrud\IEntityCrud<Entities\Triggers\Controls\Control>
	 */
	public function getEntityCrud(): DoctrineCrudCrud\IEntityCrud
	{
		if ($this->entityCrud === null) {
			$this->entityCrud = $this->entityCrudFactory->create(Entities\Triggers\Controls\Control::class);
		}

		return $this->entityCrud;
	}

}
