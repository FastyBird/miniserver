<?php declare(strict_types = 1);

/**
 * Manager.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:UIModule!
 * @subpackage     Models
 * @since          1.0.0
 *
 * @date           25.05.20
 */

namespace FastyBird\Module\Ui\Models\Entities\Widgets\DataSources;

use Doctrine\DBAL;
use FastyBird\Core\Exceptions;
use FastyBird\Core\Exceptions as DoctrineCrudExceptions;
use FastyBird\Core\Persistence\DoctrineCrud\Crud as DoctrineCrudCrud;
use FastyBird\Module\Ui\Entities;
use FastyBird\Module\Ui\Events;
use FastyBird\Module\Ui\Models;
use Nette;
use Nette\Utils;
use Psr\EventDispatcher;
use function assert;

/**
 * Widgets data sources entities manager
 *
 * @package        FastyBird:UIModule!
 * @subpackage     Models
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class Manager
{

	use Nette\SmartObject;

	/** @var DoctrineCrudCrud\IEntityCrud<Entities\Widgets\DataSources\DataSource>|null */
	private DoctrineCrudCrud\IEntityCrud|null $entityCrud = null;

	/**
	 * @param DoctrineCrudCrud\IEntityCrudFactory<Entities\Widgets\DataSources\DataSource> $entityCrudFactory
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
	): Entities\Widgets\DataSources\DataSource
	{
		$entity = $this->getEntityCrud()->getEntityCreator()->create($values);
		assert($entity instanceof Entities\Widgets\DataSources\DataSource);

		$this->dispatcher?->dispatch(new Events\EntityCreated($entity));

		return $entity;
	}

	/**
	 * @throws DBAL\Exception\UniqueConstraintViolationException
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 */
	public function update(
		Entities\Widgets\DataSources\DataSource $entity,
		Utils\ArrayHash $values,
	): Entities\Widgets\DataSources\DataSource
	{
		$entity = $this->getEntityCrud()->getEntityUpdater()->update($values, $entity);
		assert($entity instanceof Entities\Widgets\DataSources\DataSource);

		$this->dispatcher?->dispatch(new Events\EntityUpdated($entity));

		return $entity;
	}

	/**
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 */
	public function delete(Entities\Widgets\DataSources\DataSource $entity): bool
	{
		// Delete entity from database
		$result = $this->getEntityCrud()->getEntityDeleter()->delete($entity);

		if ($result) {
			$this->dispatcher?->dispatch(new Events\EntityDeleted($entity));
		}

		return $result;
	}

	/**
	 * @return DoctrineCrudCrud\IEntityCrud<Entities\Widgets\DataSources\DataSource>
	 */
	public function getEntityCrud(): DoctrineCrudCrud\IEntityCrud
	{
		if ($this->entityCrud === null) {
			$this->entityCrud = $this->entityCrudFactory->create(Entities\Widgets\DataSources\DataSource::class);
		}

		return $this->entityCrud;
	}

}
