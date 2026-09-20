<?php declare(strict_types = 1);

/**
 * EmailsManager.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:AccountsModule!
 * @subpackage     Models
 * @since          1.0.0
 *
 * @date           30.03.20
 */

namespace FastyBird\Module\Accounts\Models\Entities\Emails;

use Doctrine\DBAL;
use FastyBird\Core\Exceptions\DoctrineCrud as DoctrineCrudExceptions;
use FastyBird\Core\Persistence\DoctrineCrud\Crud as DoctrineCrudCrud;
use FastyBird\Module\Accounts\Entities;
use FastyBird\Module\Accounts\Models;
use Nette;
use Nette\Utils;
use function assert;

/**
 * Accounts emails address entities manager
 *
 * @package        FastyBird:AccountsModule!
 * @subpackage     Models
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class EmailsManager
{

	use Nette\SmartObject;

	/** @var DoctrineCrudCrud\IEntityCrud<Entities\Emails\Email>|null */
	private DoctrineCrudCrud\IEntityCrud|null $entityCrud = null;

	/**
	 * @param DoctrineCrudCrud\IEntityCrudFactory<Entities\Emails\Email> $entityCrudFactory
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
	public function create(Utils\ArrayHash $values): Entities\Emails\Email
	{
		$entity = $this->getEntityCrud()->getEntityCreator()->create($values);
		assert($entity instanceof Entities\Emails\Email);

		return $entity;
	}

	/**
	 * @throws DBAL\Exception\UniqueConstraintViolationException
	 * @throws DoctrineCrudExceptions\InvalidArgument
	 * @throws DoctrineCrudExceptions\InvalidState
	 */
	public function update(
		Entities\Emails\Email $entity,
		Utils\ArrayHash $values,
	): Entities\Emails\Email
	{
		$entity = $this->getEntityCrud()->getEntityUpdater()->update($values, $entity);
		assert($entity instanceof Entities\Emails\Email);

		return $entity;
	}

	/**
	 * @throws DoctrineCrudExceptions\InvalidArgument
	 * @throws DoctrineCrudExceptions\InvalidState
	 */
	public function delete(Entities\Emails\Email $entity): bool
	{
		// Delete entity from database
		return $this->getEntityCrud()->getEntityDeleter()->delete($entity);
	}

	/**
	 * @return DoctrineCrudCrud\IEntityCrud<Entities\Emails\Email>
	 */
	public function getEntityCrud(): DoctrineCrudCrud\IEntityCrud
	{
		if ($this->entityCrud === null) {
			$this->entityCrud = $this->entityCrudFactory->create(Entities\Emails\Email::class);
		}

		return $this->entityCrud;
	}

}
