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

namespace FastyBird\Module\Ui\Models\Entities\Widgets\Displays;

use Doctrine\DBAL;
use FastyBird\Core\Exceptions as CoreExceptions;
use FastyBird\Core\Persistence\Crud;
use FastyBird\Core\Persistence\Exceptions as PersistenceExceptions;
use FastyBird\Module\Ui\Entities;
use FastyBird\Module\Ui\Events;
use FastyBird\Module\Ui\Models;
use Nette;
use Nette\Utils;
use Psr\EventDispatcher;
use ReflectionException;
use function assert;

/**
 * Widgets displays entities manager
 *
 * @package        FastyBird:UIModule!
 * @subpackage     Models
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class Manager
{

	use Nette\SmartObject;

	/** @var Crud\EntityCrud<Entities\Widgets\Displays\Display>|null */
	private Crud\EntityCrud|null $entityCrud = null;

	/**
	 * @param Crud\CrudFactory<Entities\Widgets\Displays\Display> $entityCrudFactory
	 */
	public function __construct(
		private readonly Crud\CrudFactory $entityCrudFactory,
		private readonly EventDispatcher\EventDispatcherInterface|null $dispatcher = null,
	)
	{
	}

	/**
	 * @throws DBAL\Exception\UniqueConstraintViolationException
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws PersistenceExceptions\EntityCreation
	 * @throws ReflectionException
	 */
	public function update(
		Entities\Widgets\Displays\Display $entity,
		Utils\ArrayHash $values,
	): Entities\Widgets\Displays\Display
	{
		$entity = $this->getEntityCrud()->getEntityUpdater()->update($values, $entity);
		assert($entity instanceof Entities\Widgets\Displays\Display);

		$this->dispatcher?->dispatch(new Events\EntityUpdated($entity));

		return $entity;
	}

	/**
	 * @return Crud\EntityCrud<Entities\Widgets\Displays\Display>
	 */
	public function getEntityCrud(): Crud\EntityCrud
	{
		if ($this->entityCrud === null) {
			$this->entityCrud = $this->entityCrudFactory->create(Entities\Widgets\Displays\Display::class);
		}

		return $this->entityCrud;
	}

}
