<?php declare(strict_types = 1);

/**
 * PropertiesManager.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModule!
 * @subpackage     Models
 * @since          1.0.0
 *
 * @date           08.02.22
 */

namespace FastyBird\Module\Devices\Models\Entities\Connectors\Properties;

use Doctrine\DBAL;
use FastyBird\Core\Exceptions;
use FastyBird\Core\Exceptions\DoctrineCrud as DoctrineCrudExceptions;
use FastyBird\Core\Persistence\DoctrineCrud\Crud as DoctrineCrudCrud;
use FastyBird\Module\Devices\Entities;
use FastyBird\Module\Devices\Events;
use FastyBird\Module\Devices\Models;
use Nette;
use Nette\Utils;
use Psr\EventDispatcher;
use function assert;

/**
 * Connectors properties entities manager
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     Models
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class PropertiesManager
{

	use Nette\SmartObject;

	/** @var DoctrineCrudCrud\IEntityCrud<Entities\Connectors\Properties\Property>|null */
	private DoctrineCrudCrud\IEntityCrud|null $entityCrud = null;

	/**
	 * @param DoctrineCrudCrud\IEntityCrudFactory<Entities\Connectors\Properties\Property> $entityCrudFactory
	 */
	public function __construct(
		private readonly DoctrineCrudCrud\IEntityCrudFactory $entityCrudFactory,
		private readonly EventDispatcher\EventDispatcherInterface|null $dispatcher = null,
	)
	{
	}

	/**
	 * @throws DBAL\Exception\UniqueConstraintViolationException
	 * @throws DoctrineCrudExceptions\EntityCreation
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 */
	public function create(
		Utils\ArrayHash $values,
	): Entities\Connectors\Properties\Property
	{
		$entity = $this->getEntityCrud()->getEntityCreator()->create($values);
		assert($entity instanceof Entities\Connectors\Properties\Property);

		$this->dispatcher?->dispatch(new Events\EntityCreated($entity));

		return $entity;
	}

	/**
	 * @throws DBAL\Exception\UniqueConstraintViolationException
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 */
	public function update(
		Entities\Connectors\Properties\Property $entity,
		Utils\ArrayHash $values,
	): Entities\Connectors\Properties\Property
	{
		$entity = $this->getEntityCrud()->getEntityUpdater()->update($values, $entity);
		assert($entity instanceof Entities\Connectors\Properties\Property);

		$this->dispatcher?->dispatch(new Events\EntityUpdated($entity));

		return $entity;
	}

	/**
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 */
	public function delete(Entities\Connectors\Properties\Property $entity): bool
	{
		// Delete entity from database
		$result = $this->getEntityCrud()->getEntityDeleter()->delete($entity);

		if ($result) {
			$this->dispatcher?->dispatch(new Events\EntityDeleted($entity));
		}

		return $result;
	}

	/**
	 * @return DoctrineCrudCrud\IEntityCrud<Entities\Connectors\Properties\Property>
	 */
	public function getEntityCrud(): DoctrineCrudCrud\IEntityCrud
	{
		if ($this->entityCrud === null) {
			$this->entityCrud = $this->entityCrudFactory->create(Entities\Connectors\Properties\Property::class);
		}

		return $this->entityCrud;
	}

}
